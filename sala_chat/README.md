# 🚀 Sala de Chat con WebSockets

Un sistema de chat en tiempo real implementado con WebSockets usando Python y PHP. Permite múltiples usuarios conectarse simultáneamente y enviar mensajes en tiempo real.

## 📋 Características

- ✅ Chat en tiempo real con WebSockets
- ✅ Múltiples usuarios simultáneos
- ✅ Interfaz web simple y funcional
- ✅ Servidor Python robusto
- ✅ Cliente PHP con JavaScript
- ✅ Manejo automático de desconexiones
- ✅ Sin mensajes duplicados

## 🛠️ Tecnologías Utilizadas

- **Backend**: Python 3.x con biblioteca `websockets`
- **Frontend**: PHP + HTML + JavaScript
- **Protocolo**: WebSocket (ws://)
- **Servidor Web**: XAMPP (Apache)

## 📁 Estructura del Proyecto

```
sala_chat/
├── server.py          # Servidor WebSocket en Python
├── client.php         # Cliente web (interfaz de chat)
├── websockets.php       # Biblioteca WebSocket para PHP
├── users.php          # Clases de usuarios
├── SalaChatServer.php # Servidor WebSocket en PHP
├── testwebsock.php    # Cliente de prueba
└── README.md          # Este archivo
```

## 🚀 Instalación y Configuración

### Prerrequisitos

- Python 3.x
- XAMPP (Apache + PHP)
- Navegador web moderno

### 1. Clonar el Repositorio

```bash
git clone https://github.com/Sesuli/WEBSOCKETS.git
cd WEBSOCKETS
```

### 2. Instalar Dependencias de Python

```bash
pip install websockets
```

### 3. Configurar XAMPP

1. Copia la carpeta `WEBSOCKETS` a `C:\xampp\htdocs\`
2. Inicia Apache en XAMPP Control Panel

## 🎯 Uso

### 1. Iniciar el Servidor WebSocket

```bash
cd C:\xampp\htdocs\WEBSOCKETS
python server.py
```

Deberías ver:
```
🚀 Servidor WebSocket en ws://localhost:8000
```

### 2. Abrir el Cliente Web

1. Abre tu navegador
2. Ve a `http://localhost/WEBSOCKETS/client.php`
3. Ingresa tu alias (nombre de usuario)
4. ¡Comienza a chatear!

### 3. Conectar Múltiples Usuarios

- Abre varias pestañas del navegador
- Cada pestaña representa un usuario diferente
- Los mensajes se sincronizan en tiempo real

## 🔧 Configuración Avanzada

### Cambiar Puerto del Servidor

Edita `server.py` línea 19:
```python
async with websockets.serve(handler, "localhost", 8000):  # Cambia 8000 por tu puerto
```

Y actualiza `client.php` línea 26:
```javascript
var host = "ws://localhost:8000";  // Cambia 8000 por tu puerto
```

### Cambiar Dirección IP

Para permitir conexiones desde otras máquinas:
```python
async with websockets.serve(handler, "0.0.0.0", 8000):  # Escucha en todas las interfaces
```

## 📝 Cómo Funciona

### Flujo de Mensajes

1. **Usuario escribe mensaje** → Cliente PHP
2. **Cliente envía** → Servidor Python via WebSocket
3. **Servidor reenvía** → Todos los clientes conectados
4. **Todos ven el mensaje** → En tiempo real

### Arquitectura

```
[Cliente 1] ←→ [Servidor Python] ←→ [Cliente 2]
     ↓              ↓                    ↓
[Cliente 3] ←→ [WebSocket ws://] ←→ [Cliente N]
```

## 🐛 Solución de Problemas

### Error: "AttributeError: 'ServerConnection' object has no attribute 'open'"

**Solución**: Ya está corregido en el código actual. El problema era usar `client.open` en lugar de `not client.closed`.

### Error: "cannot access local variable 'clients'"

**Solución**: Ya está corregido. Se agregó `global clients` en la función handler.

### Mensajes Duplicados

**Solución**: Ya está corregido. Se eliminó la línea que mostraba el mensaje dos veces en el cliente.

### Puerto en Uso

Si el puerto 8000 está ocupado:
```bash
# En Windows
netstat -ano | findstr :8000
taskkill /PID <PID_NUMBER> /F
```

## 🔒 Seguridad

- El servidor actual es para desarrollo local
- Para producción, implementa autenticación
- Considera usar WSS (WebSocket Secure) con SSL

## 🚀 Mejoras Futuras

- [ ] Autenticación de usuarios
- [ ] Salas de chat separadas
- [ ] Historial de mensajes
- [ ] Emojis y archivos
- [ ] Notificaciones push
- [ ] Base de datos para persistencia

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Brian Caceres**
- GitHub: [@Brianzve1](https://github.com/Brianzve1)
- Email: briancaceres678@gmail.com

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📞 Soporte

Si tienes problemas o preguntas:

1. Revisa la sección de [Solución de Problemas](#-solución-de-problemas)
2. Abre un [Issue](https://github.com/Sesuli/WEBSOCKETS)
3. Contacta al autor

---

⭐ **¡Dale una estrella al proyecto si te gusta!** ⭐
