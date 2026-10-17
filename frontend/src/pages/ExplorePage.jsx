import React from 'react';

export default function ExplorePage() {
  return (
    <div>
      <h2 className="fw-bold mb-4 text-primary">Explore & Discovery</h2>
      <div className="row g-3 mb-4">
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <span className="fs-1">🚀</span>
            <h5 className="fw-bold mt-2">Tech Trends</h5>
            <small className="text-muted">Discover popular AI & software updates</small>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <span className="fs-1">🎨</span>
            <h5 className="fw-bold mt-2">Creative Studio</h5>
            <small className="text-muted">Top Reels and Media Showcase</small>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
            <span className="fs-1">💼</span>
            <h5 className="fw-bold mt-2">Pro Rooms</h5>
            <small className="text-muted">Team collaboration spaces</small>
          </div>
        </div>
      </div>
    </div>
  );
}
