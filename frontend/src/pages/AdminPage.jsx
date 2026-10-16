import React from 'react';

export default function AdminPage() {
  return (
    <div>
      <h2 className="fw-bold mb-4">Superadmin Control Center</h2>
      <div className="row gap-3 mb-4">
        <div className="col qc-card">
          <h4>Users</h4>
          <span className="fs-2 text-primary fw-bold">1,240</span>
        </div>
        <div className="col qc-card">
          <h4>Synthetic AI Ghost Users</h4>
          <span className="fs-2 text-warning fw-bold">42</span>
        </div>
        <div className="col qc-card">
          <h4>Moderation Queue</h4>
          <span className="fs-2 text-danger fw-bold">3 Pending</span>
        </div>
      </div>
      <div className="qc-card">
        <h5 className="fw-bold mb-3">System Health & Services</h5>
        <ul className="list-group list-group-flush bg-dark text-light">
          <li className="list-group-item bg-dark text-light">WebSocket Channels: Active</li>
          <li className="list-group-item bg-dark text-light">WebRTC Calling Relay: Ready</li>
          <li className="list-group-item bg-dark text-light">Bachs.io Webhook Handler: Verified</li>
        </ul>
      </div>
    </div>
  );
}
