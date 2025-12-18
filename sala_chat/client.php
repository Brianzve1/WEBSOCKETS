<html>
<head>
<title>WebSocket</title>
<style type="text/css">
html,body {
    font: normal 0.9em arial, helvetica;
}
#log {
    width: 600px;
    height: 300px;
    border: 1px solid #7F9DB9;
    overflow: auto;
    padding: 5px;
    background: #f8f8f8;
}
#msg {
    width: 400px;
}
</style>
<script type="text/javascript">
var socket;
var alias = "";

function init() {
	// Apuntar a la IP/Puerto configurado en el contructor del WebServerSocket, que es donde está escuchando el socket.
    var host = "ws://localhost:8000"; 
    try {
        socket = new WebSocket(host);
        log("WebSocket - status " + socket.readyState);

        socket.onopen = function(msg) {
            log("Bienvenido al chat. Conectado - Estado " + this.readyState);
        };
        socket.onmessage = function(msg) {
            log(msg.data);
        };
        socket.onclose = function(msg) {
            log("Desconectado - estado " + this.readyState);
        };
    }
    catch (ex) {
        log(ex);
    }
    $("msg").focus();
}

function send() {
    var txt = $("msg");
    var message = txt.value.trim();

    if (!message) {
        alert("El mensaje no puede estar vacío");
        return;
    }

    alias = $("alias").value.trim();  
    if (!alias) {
        alert("Debes ingresar un alias");
        return;
    }

    var fullMessage = alias + ": " + message;

    txt.value = "";
    txt.focus();
    try {
        socket.send(fullMessage);
    } catch (ex) {
        log(ex);
    }
}

function quit() {
    if (socket != null) {
        log("Adiós!");
        socket.close();
        socket = null;
    }
}

function reconnect() {
    quit();
    init();
}

// Utilities
function $(id) { return document.getElementById(id); }
function log(msg) { $("log").innerHTML += "<br>" + msg; }
function onkey(event) { if (event.keyCode == 13) { send(); } }
</script>
</head>
<body onload="init()">
<h3>WebSocket </h3>

<div id="log"></div>
<br>
<label>Alias: </label>
<input id="alias" type="text" placeholder="Nombre">
<br><br>
<input id="msg" type="text" onkeypress="onkey(event)" placeholder="Escribe un mensaje"/>
<button onclick="send()">Enviar</button>
<button onclick="quit()">Salir</button>
<button onclick="reconnect()">Reconectar</button>

</body>
</html>
