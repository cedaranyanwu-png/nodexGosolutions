import React, { useState } from 'react';

export default function MessagesPage() {
  const [messages, setMessages] = useState([
    { id: 1, sender: 'Alice', content: 'Hey there! How is QuickChat working?', time: '10:14 AM' },
    { id: 2, sender: 'You', content: 'Everything is running smoothly via Django Channels WebSockets!', time: '10:15 AM' }
  ]);
  const [text, setText] = useState('');

  const handleSend = (e) => {
    e.preventDefault();
    if (!text) return;
    setMessages([...messages, { id: Date.now(), sender: 'You', content: text, time: 'Just now' }]);
    setText('');
  };

  return (
    <div>
      <h2 className="fw-bold mb-4">WhatsApp-style Messaging</h2>
      <div className="qc-card" style={{ height: '500px', display: 'flex', flexDirection: 'column' }}>
        <div style={{ flex: 1, overflowY: 'auto' }} className="d-flex flex-column gap-3 mb-3">
          {messages.map((m) => (
            <div key={m.id} className={`p-3 rounded style ${m.sender === 'You' ? 'bg-primary text-end ms-auto' : 'bg-dark text-start me-auto'}`} style={{ maxWidth: '70%' }}>
              <small className="d-block text-muted mb-1">{m.sender}</small>
              <div>{m.content}</div>
              <small className="text-light opacity-50" style={{ fontSize: '0.7rem' }}>{m.time}</small>
            </div>
          ))}
        </div>
        <form onSubmit={handleSend} className="d-flex gap-2">
          <input
            type="text"
            className="form-control bg-dark text-light border-secondary"
            placeholder="Type a message or record a voice note..."
            value={text}
            onChange={(e) => setText(e.target.value)}
          />
          <button className="btn btn-qc-primary" type="submit">Send</button>
        </form>
      </div>
    </div>
  );
}
