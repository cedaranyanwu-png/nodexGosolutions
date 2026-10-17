import React from 'react';

export default function GenericPage({ title, description }) {
  return (
    <div>
      <h2 className="fw-bold mb-4 text-primary">{title}</h2>
      <div className="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <p className="lead text-secondary mb-2">{description}</p>
        <p className="text-muted mb-0">Explore and interact with {title} features in QuickChat.</p>
      </div>
    </div>
  );
}
