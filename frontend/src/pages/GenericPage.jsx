import React from 'react';

export default function GenericPage({ title, description }) {
  return (
    <div>
      <h2 className="fw-bold mb-4">{title}</h2>
      <div className="qc-card">
        <p className="lead text-light">{description}</p>
        <p className="text-muted">Explore and interact with {title} features in QuickChat.</p>
      </div>
    </div>
  );
}
