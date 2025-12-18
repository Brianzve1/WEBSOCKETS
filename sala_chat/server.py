import asyncio
import websockets

clients = set()

async def handler(websocket):
    global clients
    clients.add(websocket)
    try:
        async for message in websocket:
            print("📩 Mensaje recibido:", message)
            # reenviar a todos los conectados (incluyendo al que envió)
            disconnected_clients = set()
            for client in clients:
                try:
                    await client.send(message)
                except websockets.exceptions.ConnectionClosed:
                    disconnected_clients.add(client)
            
            # Remove disconnected clients
            clients -= disconnected_clients
    except websockets.exceptions.ConnectionClosed:
        pass
    finally:
        clients.discard(websocket)

async def main():
    async with websockets.serve(handler, "localhost", 8000):
        print("🚀 Servidor WebSocket en ws://localhost:8000")
        await asyncio.Future()  # correr para siempre

asyncio.run(main())
