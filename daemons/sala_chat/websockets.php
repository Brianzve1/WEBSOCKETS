<?php
require_once('users.php');

abstract class WebSocketServer {
    protected $userClass = 'WebSocketUser';
    protected $maxBufferSize;
    protected $master;
    protected $sockets = [];
    protected $users = [];
    protected $heldMessages = [];
    protected $interactive = true;
    protected $headerOriginRequired = false;
    protected $headerSecWebSocketProtocolRequired = false;
    protected $headerSecWebSocketExtensionsRequired = false;

    function __construct($addr, $port, $bufferLength = 2048) {
        $this->maxBufferSize = $bufferLength;
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1) or die("Failed: socket_option()");
        socket_bind($this->master, $addr, $port) or die("Failed: socket_bind()");
        socket_listen($this->master,20) or die("Failed: socket_listen()");
        $this->sockets['m'] = $this->master;
        $this->stdout("Server started\nListening on: $addr:$port\nMaster socket initialized");
    }

    abstract protected function process($user,$message);
    abstract protected function connected($user);
    abstract protected function closed($user);

    protected function connecting($user) {}

    protected function send($user, $message) {
        if ($user->handshake) {
            $message = $this->frame($message,$user);
            @socket_write($user->socket, $message, strlen($message));
        } else if (isset($user->id)) {
            $this->heldMessages[] = ['user' => $user, 'message' => $message];
        }
    }

    protected function tick() {}
    protected function _tick() {
        foreach ($this->heldMessages as $key => $hm) {
            $found = false;
            foreach ($this->users as $currentUser) {
                if ($hm['user']->socket == $currentUser->socket) {
                    $found = true;
                    if ($currentUser->handshake) {
                        unset($this->heldMessages[$key]);
                        $this->send($currentUser, $hm['message']);
                    }
                }
            }
            if (!$found) unset($this->heldMessages[$key]);
        }
    }

    public function run() {
        while(true) {
            if (empty($this->sockets)) $this->sockets['m'] = $this->master;
            $read = $this->sockets; $write = $except = null;
            $this->_tick(); $this->tick();
            @socket_select($read,$write,$except,1);
            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $client = socket_accept($socket);
                    if ($client < 0) { $this->stderr("Failed: socket_accept()"); continue; }
                    $user = $this->connect($client);
                    $this->stdout("Client connected. User ID: " . $user->id);
                } else {
                    $numBytes = @socket_recv($socket, $buffer, $this->maxBufferSize, 0);
                    if ($numBytes === false) {
                        $sockErrNo = socket_last_error($socket);
                        $this->stderr('Socket error ('.$sockErrNo.'): ' . socket_strerror($sockErrNo));
                        $this->disconnect($socket, true, $sockErrNo);
                    } elseif ($numBytes == 0) {
                        $this->disconnect($socket);
                        $this->stderr("Client disconnected. TCP connection lost.");
                    } else {
                        $user = $this->getUserBySocket($socket);
                        if (!$user->handshake) {
                            $tmp = str_replace("\r", '', $buffer);
                            if (strpos($tmp, "\n\n") === false) continue; 
                            $this->doHandshake($user,$buffer);
                        } else {
                            $this->split_packet($numBytes,$buffer, $user);
                        }
                    }
                }
            }
        }
    }

    protected function connect($socket) {
        $user = new $this->userClass(uniqid('u'), $socket);
        $this->users[$user->id] = $user;
        $this->sockets[$user->id] = $socket;
        $this->connecting($user);
        return $user; // Devuelve el usuario creado
    }

    protected function disconnect($socket, $triggerClosed = true, $sockErrNo = null) {
        $disconnectedUser = $this->getUserBySocket($socket);
        if ($disconnectedUser !== null) {
            unset($this->users[$disconnectedUser->id]);
            if (isset($this->sockets[$disconnectedUser->id])) unset($this->sockets[$disconnectedUser->id]);
            if ($triggerClosed) {
                $this->stdout("Client disconnected. User ID: ".$disconnectedUser->id);
                $this->closed($disconnectedUser);
                socket_close($disconnectedUser->socket);
            } else {
                $message = $this->frame('', $disconnectedUser, 'close');
                @socket_write($disconnectedUser->socket, $message, strlen($message));
            }
        }
    }

    protected function doHandshake($user, $buffer) {
        $magicGUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $headers = []; $lines = explode("\n",$buffer);
        foreach ($lines as $line) {
            if (strpos($line,":") !== false) {
                $header = explode(":",$line,2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            } elseif (stripos($line,"get ") !== false) {
                preg_match("/GET (.*) HTTP/i", $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }
        $user->requestedResource = $headers['get'] ?? '';
        if (!$this->checkHost($headers['host'] ?? '')) {
            socket_write($user->socket,"HTTP/1.1 400 Bad Request",strlen("HTTP/1.1 400 Bad Request"));
            $this->disconnect($user->socket); return;
        }

        $webSocketKeyHash = sha1($headers['sec-websocket-key'] . $magicGUID);
        $rawToken = ""; for ($i=0;$i<20;$i++) $rawToken .= chr(hexdec(substr($webSocketKeyHash,$i*2,2)));
        $handshakeToken = base64_encode($rawToken) . "\r\n";
        $subProtocol = $extensions = "";
        $handshakeResponse = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $handshakeToken$subProtocol$extensions\r\n";
        socket_write($user->socket,$handshakeResponse,strlen($handshakeResponse));
        $user->handshake = $buffer;
        $this->connected($user);
    }

    protected function checkHost($hostName) { return true; }

    protected function getUserBySocket($socket) {
        foreach ($this->users as $user) if ($user->socket == $socket) return $user;
        return null;
    }

    public function stdout($message) { if ($this->interactive) echo "$message\n"; }
    public function stderr($message) { if ($this->interactive) echo "$message\n"; }

    protected function frame($message, $user, $messageType='text', $messageContinues=false) {
        $b1 = match($messageType) {
            'continuous'=>0,
            'text'=>($user->sendingContinuous?0:1),
            'binary'=>($user->sendingContinuous?0:2),
            'close'=>8,
            'ping'=>9,
            'pong'=>10,
        };
        if ($messageContinues) $user->sendingContinuous = true;
        else { $b1+=128; $user->sendingContinuous=false; }
        $length = strlen($message); $lengthField="";
        if ($length<126) $b2=$length;
        elseif ($length<65536) { $b2=126; $lengthField=pack('n',$length); }
        else { $b2=127; $lengthField=pack('J',$length); }
        return chr($b1) . chr($b2) . $lengthField . $message;
    }

    protected function split_packet($length,$packet, $user) {
        if ($user->handlingPartialPacket) { $packet=$user->partialBuffer.$packet; $user->handlingPartialPacket=false; $length=strlen($packet); }
        $frame_pos=0;
        while($frame_pos<$length) {
            $headers = $this->extractHeaders($packet);
            $framesize=$headers['length']+2+($headers['hasmask']?4:0)+($headers['length']>125?($headers['length']>65535?8:2):0);
            $frame=substr($packet,$frame_pos,$framesize);
            if (($message = $this->deframe($frame, $user,$headers)) !== FALSE) {
                if ($user->hasSentClose) $this->disconnect($user->socket);
                else $this->process($user, $message);
            }
            $frame_pos+=$framesize; $packet=substr($packet,$frame_pos);
        }
    }

    protected function extractHeaders($message) {
        $header=['fin'=>ord($message[0]) & 128,'rsv1'=>ord($message[0]) & 64,'rsv2'=>ord($message[0]) &32,'rsv3'=>ord($message[0]) &16,'opcode'=>ord($message[0])&15,'hasmask'=>ord($message[1])&128,'length'=>0,'mask'=>""];
        $header['length']=(ord($message[1])>=128)?ord($message[1])-128:ord($message[1]);
        if($header['length']==126){$header['mask']=$message[4].$message[5].$message[6].$message[7]; $header['length']=ord($message[2])*256+ord($message[3]);}
        elseif($header['length']==127){$header['mask']=$message[10].$message[11].$message[12].$message[13];$header['length']=ord($message[2])*4294967296+ord($message[3])*16777216+ord($message[4])*65536+ord($message[5])*256+ord($message[6]);}
        elseif($header['hasmask']) $header['mask']=$message[2].$message[3].$message[4].$message[5];
        return $header;
    }

    protected function deframe($message,&$user,$headers=null) {
        if(!$headers) $headers=$this->extractHeaders($message);
        if($headers['opcode']==8){$user->hasSentClose=true; return "";}
        if($headers['opcode']==9){$reply=$this->frame(substr($message,2),$user,'pong'); socket_write($user->socket,$reply,strlen($reply)); return false;}
        $payload=$user->partialMessage.$this->extractPayload($message,$headers);
        if($headers['fin']) $user->partialMessage=""; else $user->partialMessage=$payload;
        return $payload;
    }

    protected function extractPayload($message,$headers) {
        $offset=2+($headers['hasmask']?4:0)+($headers['length']>125?($headers['length']>65535?8:2):0);
        return substr($message,$offset);
    }

    protected function applyMask($headers,$payload) {
        if(!$headers['hasmask']) return $payload;
        $mask=$headers['mask']; $effectiveMask="";
        while(strlen($effectiveMask)<strlen($payload)) $effectiveMask.=$mask;
        while(strlen($effectiveMask)>strlen($payload)) $effectiveMask=substr($effectiveMask,0,-1);
        return $effectiveMask ^ $payload;
    }
}
