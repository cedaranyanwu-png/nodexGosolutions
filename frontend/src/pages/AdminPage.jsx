import React from 'react';

export default function AdminPage() {
  return (
    <div>
      <h2 className="fw-bold mb-4 text-primary">Superadmin Control Center</h2>
      <div className="row g-4 mb-4">
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <h6 className="text-muted mb-2">Total Users</h6>
            <span className="fs-1 text-primary fw-bold">1,240</span>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <h6 className="text-muted mb-2">Synthetic AI Users</h6>
            <span className="fs-1 text-warning fw-bold">42</span>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <h6 className="text-muted mb-2">Moderation Queue</h6>
            <span className="fs-1 text-danger fw-bold">3 Pending</span>
          </div>
        </div>
      </div>
      <div className="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 className="fw-bold text-primary mb-3">System Health & Services</h5>
        <ul className="list-group list-group-flush">
          <li className="list-group-item px-0 d-flex justify-content-between align-items-center">
            <span>WebSocket Channels</span>
            <span className="badge bg-success">Active</span>
          </li>
          <li className="list-group-item px-0 d-flex justify-content-between align-items-center">
            <span>WebRTC Calling Relay</span>
            <span className="badge bg-success">Ready</span>
          </li>
          <li className="list-group-item px-0 d-flex justify-content-between align-items-center">
            <span>Bachs.io Webhook Handler</span>
            <span className="badge bg-primary">Verified</span>
          </li>
        </ul>
      </div>
    </div>
  );
}
