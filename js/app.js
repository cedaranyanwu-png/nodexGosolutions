/**
 * NodeX Interactive React Application
 * Provides real-time WebSocket communication, tenant dynamic switching,
 * live messaging feed, and status indicators.
 */

const { useState, useEffect, useRef } = React;

function App() {
    // Initial state setup from window globals passed by PHP
    const initialTenant = window.INITIAL_TENANT_DATA || {};
    const allTenants = window.ALL_TENANTS_DATA || [];

    const [currentTenant, setCurrentTenant] = useState(initialTenant);
    const [socketStatus, setSocketStatus] = useState('disconnected'); // connected, disconnected, connecting
    const [messages, setMessages] = useState([]);
    const [chatInput, setChatInput] = useState('');
    const [activeClients, setActiveClients] = useState(0);
    const socketRef = useRef(null);
    const chatEndRef = useRef(null);

    // Apply tenant theme dynamically to root CSS custom property and header elements
    const updateTheme = (tenant) => {
        document.body.style.setProperty('--theme-color', tenant.theme_color || '#2c3e50');

        // Synchronize legacy header elements if present in DOM
        const headerLogo = document.getElementById('tenant-logo');
        const headerTitle = document.getElementById('tenant-title');
        if (headerLogo) {
            headerLogo.src = tenant.logo_url;
            headerLogo.alt = `${tenant.name} Logo`;
        }
        if (headerTitle) {
            headerTitle.textContent = tenant.name;
        }
        document.title = `${tenant.name} | NodeX Multi-Tenant`;
    };

    // Auto-scroll chat to bottom
    const scrollToBottom = () => {
        if (chatEndRef.current) {
            chatEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    // WebSocket Connection Management
    useEffect(() => {
        setSocketStatus('connecting');
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        // Connect to Python WebSocket server running on port 8000
        const wsHost = window.location.hostname || 'localhost';
        const wsUrl = `${wsProtocol}//${wsHost}:8000/ws`;

        const ws = new WebSocket(wsUrl);
        socketRef.current = ws;

        ws.onopen = () => {
            setSocketStatus('connected');
            // Send initial tenant context to WebSocket server
            ws.send(JSON.stringify({
                type: 'switch_tenant',
                tenant_id: currentTenant.id,
                tenant_name: currentTenant.name
            }));
        };

        ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);

                if (data.active_clients !== undefined) {
                    setActiveClients(data.active_clients);
                }

                if (data.type === 'system') {
                    setMessages((prev) => [...prev, { id: Date.now() + Math.random(), type: 'system', text: data.message }]);
                } else if (data.type === 'tenant_switched') {
                    setMessages((prev) => [...prev, {
                        id: Date.now() + Math.random(),
                        type: 'system',
                        text: `[Real-Time Event] ${data.message}`
                    }]);
                } else if (data.type === 'chat') {
                    setMessages((prev) => [...prev, {
                        id: Date.now() + Math.random(),
                        type: 'chat',
                        sender: data.sender,
                        tenant_name: data.tenant_name,
                        text: data.message
                    }]);
                }
            } catch (err) {
                console.error("Failed to parse WebSocket message:", err);
            }
        };

        ws.onclose = () => {
            setSocketStatus('disconnected');
        };

        ws.onerror = (err) => {
            console.error("WebSocket error:", err);
            setSocketStatus('disconnected');
        };

        return () => {
            if (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING) {
                ws.close();
            }
        };
    }, []);

    // Handle Tenant Switch
    const handleSelectTenant = (tenant) => {
        setCurrentTenant(tenant);
        updateTheme(tenant);

        if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
            socketRef.current.send(JSON.stringify({
                type: 'switch_tenant',
                tenant_id: tenant.id,
                tenant_name: tenant.name
            }));
        }
    };

    // Handle Send Message
    const handleSendMessage = (e) => {
        e.preventDefault();
        if (!chatInput.trim()) return;

        const payload = {
            type: 'chat',
            sender: currentTenant.name,
            tenant_name: currentTenant.name,
            message: chatInput
        };

        if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
            socketRef.current.send(JSON.stringify(payload));
        } else {
            // Local fallback if socket not connected
            setMessages((prev) => [...prev, {
                id: Date.now(),
                type: 'chat',
                sender: currentTenant.name,
                tenant_name: currentTenant.name,
                text: chatInput + ' (Offline)'
            }]);
        }
        setChatInput('');
    };

    return (
        <div className="react-dashboard">
            {/* Live Socket Status Banner */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                <div>
                    <h2>Workspace Dashboard: <span style={{ color: currentTenant.theme_color }}>{currentTenant.name}</span></h2>
                </div>
                <div>
                    Status: {' '}
                    <span className={`status-badge status-${socketStatus}`}>
                        {socketStatus === 'connected' ? `Live Sockets (${activeClients} active)` : socketStatus}
                    </span>
                </div>
            </div>

            {/* Tenant Selection Switcher */}
            <div className="card" style={{ marginBottom: '1.5rem' }}>
                <h3>Select Active Tenant Workspace</h3>
                <div className="tenant-btn-group">
                    {allTenants.map((tenant) => (
                        <button
                            key={tenant.id}
                            className={`tenant-btn ${currentTenant.id === tenant.id ? 'active' : ''}`}
                            onClick={() => handleSelectTenant(tenant)}
                        >
                            {tenant.name}
                        </button>
                    ))}
                </div>
            </div>

            <div className="dashboard-grid">
                {/* Active Tenant Overview */}
                <div className="card">
                    <h3>Tenant Details</h3>
                    <p><strong>Description:</strong> {currentTenant.content}</p>
                    <ul>
                        <li><strong>Tenant ID:</strong> {currentTenant.id}</li>
                        <li><strong>Identifier:</strong> {currentTenant.identifier}</li>
                        <li><strong>Theme Color:</strong> <code style={{ color: currentTenant.theme_color }}>{currentTenant.theme_color}</code></li>
                    </ul>
                </div>

                {/* Real-Time Communication Hub */}
                <div className="card">
                    <h3>Real-Time Live Feed & Chat</h3>
                    <div className="chat-window">
                        {messages.length === 0 ? (
                            <p style={{ color: '#888', fontStyle: 'italic', textAlign: 'center', margin: 'auto' }}>
                                WebSocket connected. Send a message or switch workspace to broadcast events.
                            </p>
                        ) : (
                            messages.map((msg) => {
                                if (msg.type === 'system') {
                                    return <div key={msg.id} className="chat-msg system">{msg.text}</div>;
                                }
                                return (
                                    <div key={msg.id} className="chat-msg user">
                                        <strong>[{msg.tenant_name}]:</strong> {msg.text}
                                    </div>
                                );
                            })
                        )}
                        <div ref={chatEndRef} />
                    </div>
                    <form onSubmit={handleSendMessage} className="chat-input-box">
                        <input
                            type="text"
                            placeholder="Type a real-time message..."
                            value={chatInput}
                            onChange={(e) => setChatInput(e.target.value)}
                        />
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>
        </div>
    );
}

// Mount React Component into DOM
const rootElement = document.getElementById('react-app-root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(<App />);
}
