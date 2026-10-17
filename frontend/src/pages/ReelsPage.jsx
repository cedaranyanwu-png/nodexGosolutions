import React from 'react';

export default function ReelsPage() {
  return (
    <div>
      <h2 className="fw-bold mb-4 text-primary">Vertical Short Reels</h2>
      <div className="card border-0 shadow-sm rounded-4 p-5 bg-white text-center mx-auto" style={{ maxWidth: '400px' }}>
        <div className="bg-light p-4 rounded-4 mb-3">
          <span className="fs-1">🎬</span>
          <h4 className="fw-bold text-primary mt-2">Reel Video Preview</h4>
          <p className="text-secondary small">Original Audio - Creator Broadcast</p>
        </div>
        <div className="d-flex justify-content-around text-muted border-top pt-3">
          <span>❤️ 1.2K</span>
          <span>💬 84</span>
          <span>↗️ Share</span>
        </div>
      </div>
    </div>
  );
}
