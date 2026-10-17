import React from 'react';

export default function ProfilePage() {
  return (
    <div>
      <div className="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white text-center">
        <div
          className="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
          style={{ width: '80px', height: '80px', fontSize: '2rem' }}
        >
          👤
        </div>
        <h3 className="fw-bold text-primary mb-1">QuickChat User</h3>
        <p className="text-secondary mb-3">@quickchat_member</p>
        <div className="d-flex justify-content-center gap-4 border-top border-bottom py-3 mb-3">
          <div><strong className="fs-5 text-primary">12</strong> <small className="text-muted d-block">Posts</small></div>
          <div><strong className="fs-5 text-primary">145</strong> <small class="text-muted d-block">Followers</small></div>
          <div><strong className="fs-5 text-primary">98</strong> <small class="text-muted d-block">Following</small></div>
        </div>
        <p className="text-muted small">Building next-gen social media with Django, WebSockets, and React.</p>
      </div>

      {/* 3-Column Profile Grid */}
      <h5 className="fw-bold text-primary mb-3">Published Posts</h5>
      <div className="row g-3">
        <div className="col-4">
          <div className="bg-white border rounded-3 p-3 text-center shadow-sm">
            <span className="fs-3">📷</span>
          </div>
        </div>
        <div className="col-4">
          <div className="bg-white border rounded-3 p-3 text-center shadow-sm">
            <span className="fs-3">🎥</span>
          </div>
        </div>
        <div className="col-4">
          <div className="bg-white border rounded-3 p-3 text-center shadow-sm">
            <span className="fs-3">📝</span>
          </div>
        </div>
      </div>
    </div>
  );
}
