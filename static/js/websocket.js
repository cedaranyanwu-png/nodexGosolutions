/**
 * QuickChat Client WebSocket Module
 */
class QCWebSocket {
  constructor(url) {
    this.url = url;
    this.socket = null;
  }

  connect() {
    this.socket = new WebSocket(this.url);
    this.socket.onopen = () => console.log('QC WebSocket connected to', this.url);
    this.socket.onmessage = (event) => {
      const data = JSON.parse(event.data);
      console.log('QC WebSocket message:', data);
    };
    this.socket.onclose = () => {
      console.log('QC WebSocket disconnected. Retrying in 3s...');
      setTimeout(() => this.connect(), 3000);
    };
  }

  send(data) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data));
    }
  }
}
