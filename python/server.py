"""
Python WebSocket Server for NodeX Multi-Tenant Platform
Built with FastAPI and WebSockets for real-time communication, status monitoring, and live messaging.
"""

import json
import asyncio
from typing import List, Dict, Any
from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

app = FastAPI(title="NodeX Real-Time Socket Server")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class ConnectionManager:
    def __init__(self):
        self.active_connections: List[WebSocket] = []
        self.connection_meta: Dict[WebSocket, Dict[str, Any]] = {}

    async def connect(self, websocket: WebSocket):
        await websocket.accept()
        self.active_connections.append(websocket)
        self.connection_meta[websocket] = {"tenant_id": None}
        print(f"Client connected. Active connections: {len(self.active_connections)}")

    def disconnect(self, websocket: WebSocket):
        if websocket in self.active_connections:
            self.active_connections.remove(websocket)
        if websocket in self.connection_meta:
            del self.connection_meta[websocket]
        print(f"Client disconnected. Active connections: {len(self.active_connections)}")

    async def send_personal_message(self, message: dict, websocket: WebSocket):
        await websocket.send_text(json.dumps(message))

    async def broadcast(self, message: dict):
        payload = json.dumps(message)
        for connection in self.active_connections:
            try:
                await connection.send_text(payload)
            except Exception as e:
                print(f"Error broadcasting to client: {e}")

manager = ConnectionManager()

@app.get("/health")
async def health_check():
    return {
        "status": "online",
        "active_clients": len(manager.active_connections),
        "service": "NodeX Real-Time WebSocket Engine"
    }

@app.websocket("/ws")
async def websocket_endpoint(websocket: WebSocket):
    await manager.connect(websocket)
    try:
        # Welcome message
        await manager.send_personal_message({
            "type": "system",
            "message": "Connected to NodeX Real-Time WebSocket Engine.",
            "active_clients": len(manager.active_connections)
        }, websocket)

        # Notify all users of new client connection
        await manager.broadcast({
            "type": "system",
            "message": f"A new client joined real-time session. Total active: {len(manager.active_connections)}",
            "active_clients": len(manager.active_connections)
        })

        while True:
            data_raw = await websocket.receive_text()
            try:
                data = json.loads(data_raw)
            except json.JSONDecodeError:
                data = {"type": "chat", "message": data_raw}

            msg_type = data.get("type", "chat")

            if msg_type == "switch_tenant":
                tenant_id = data.get("tenant_id")
                tenant_name = data.get("tenant_name")
                manager.connection_meta[websocket]["tenant_id"] = tenant_id

                await manager.broadcast({
                    "type": "tenant_switched",
                    "tenant_id": tenant_id,
                    "tenant_name": tenant_name,
                    "message": f"Client switched active workspace to {tenant_name}."
                })

            elif msg_type == "chat":
                sender = data.get("sender", "Anonymous")
                text = data.get("message", "")
                tenant_name = data.get("tenant_name", "Global")

                await manager.broadcast({
                    "type": "chat",
                    "sender": sender,
                    "tenant_name": tenant_name,
                    "message": text,
                    "timestamp": asyncio.get_event_loop().time()
                })

            elif msg_type == "ping":
                await manager.send_personal_message({"type": "pong"}, websocket)

    except WebSocketDisconnect:
        manager.disconnect(websocket)
        await manager.broadcast({
            "type": "system",
            "message": f"A client disconnected. Total active: {len(manager.active_connections)}",
            "active_clients": len(manager.active_connections)
        })

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)
