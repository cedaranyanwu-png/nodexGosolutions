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
      <h2 className="fw-bold mb-4 text-primary">WhatsApp-Style Messaging</h2>
      <div className="card border-0 shadow-sm rounded-4 p-4 bg-white" style={{ height: '520px', display: 'flex', flexDirection: 'column' }}>
        <div style={{ flex: 1, overflowY: 'auto' }} className="d-flex flex-column gap-3 mb-3">
          {messages.map((m) => (
            <div
              key={m.id}
              className={`p-3 rounded-4 ${
                m.sender === 'You' ? 'bg-primary text-white text-end ms-auto' : 'bg-light text-dark text-start me-auto border'
              }`}
              style={{ maxWidth: '65%' }}
            >
              <small className={`d-block mb-1 ${m.sender === 'You' ? 'text-white-50' : 'text-primary fw-bold'}`}>
                {m.sender}
              </small>
              <div className="fs-6">{m.content}</div>
              <small className={m.sender === 'You' ? 'text-white-50' : 'text-muted'} style={{ fontSize: '0.75rem' }}>
                {m.time}
              </small>
            </div>
          ))}
        </div>
        <form onSubmit={handleSend} className="d-flex gap-2">
          <input
            type="text"
            className="form-control form-control-lg border-light-subtle rounded-3"
            placeholder="Type a message or record a voice note..."
            value={text}
            onChange={(e) => setText(e.target.value)}
          />
          <button className="btn btn-primary btn-lg fw-bold px-4 rounded-3" type="submit">
            Send
          </button>
        </form>
      </div>
    </div>
  );
}
