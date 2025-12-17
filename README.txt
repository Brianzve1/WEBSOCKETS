1 Nos aseguramos de la ubicación de PHP y los archivos del servidor.

Antes teníamos rutas equivocadas (xamppp vs xampp) y archivos fuera de lugar.

Confirmamos que php.exe estaba en C:\xamppp\php\php.exe 
y SalaChatServer.php en C:\xamppp\htdocs\WEBSOCKET\daemons\sala_chat\.

2 cd C:\xamppp\htdocs\WEBSOCKET\daemons\sala_chat

3 C:\xamppp\php\php.exe -q SalaChatServer.php

4 Esto arrancó el servidor.

Aparecieron mensajes como:

Server started
Listening on: localhost:9000
Master socket initialized
Client connected. User ID: u6943176fa92e2

5 Resultado:

Ahora el servidor WebSocket está activo en el puerto 9000.

El siguiente paso es abrir el cliente HTML en el navegador 
para enviar y recibir mensajes a través de ese servidor.

