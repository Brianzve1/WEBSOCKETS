#!/usr/bin/env php
<?php
require_once('websockets.php');

class SalaChatServer extends WebSocketServer {  

    // Ahora está definido aquí para evitar el error call to undefined method
    protected function checkHost($hostName) { 
        return true; 
    }

    protected function process($user, $message){
        $data = json_decode($message, true);
        if (!$data) return;

        // Setear nickname
        if (isset($data['type']) && $data['type'] === 'setNick') {
            $nick = trim($data['nick'] ?? 'Anónimo');
            $user->nick = substr($nick, 0, 20);
            $this->stdout("Nick seteado: {$user->nick}");
            return;
        }

        // Mensaje normal
        if (isset($data['type']) && $data['type'] === 'message') {
            $text = trim($data['text'] ?? '');
            if ($text === '') return;
            $nick = $user->nick ?? 'Anónimo';
            $payload = json_encode([
                'type' => 'message',
                'nick' => $nick,
                'text' => $text,
                'time' => date('H:i:s')
            ], JSON_UNESCAPED_UNICODE);

            // Enviar a todos los usuarios conectados
            foreach ($this->users as $currentUser) {
                $this->send($currentUser, $payload);
            }
        }
    }

    protected function connected($user){
        $user->nick = 'Anónimo'; // valor por defecto
        $this->stdout("User connected: {$user->id}");
    }

    protected function closed($user){
        $this->stdout("User disconnected: {$user->id}");
    }
}

// Iniciamos el servidor
$chatServer = new SalaChatServer("localhost", "9000");

try {
    $chatServer->run();
} catch(Exception $e){
    $chatServer->stdout($e->getMessage());
}
