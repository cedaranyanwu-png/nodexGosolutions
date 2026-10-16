import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function MainLayout({ children }) {
  const location = useLocation();
  const { user } = useAuth();

  const navItems = [
    { label: 'Home Feed', path: '/' },
    { label: 'Explore', path: '/explore' },
    { label: 'Reels', path: '/reels' },
    { label: 'Messages', path: '/messages' },
    { label: 'Calls', path: '/calls' },
    { label: 'Communities', path: '/communities' },
    { label: 'Pro Rooms', path: '/rooms' },
    { label: 'Media Studio', path: '/media-studio' },
    { label: 'Creator Hub', path: '/creator-hub' },
    { label: 'Superadmin', path: '/admin-panel' },
  ];

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-md-3 col-lg-2 qc-nav p-3 d-none d-md-block">
          <h3 className="fw-bold mb-4" style={{ background: 'linear-gradient(135deg, #2563eb, #7c3aed)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
            QuickChat
          </h3>
          <ul className="nav flex-column gap-2">
            {navItems.map((item) => (
              <li className="nav-item" key={item.path}>
                <Link
                  to={item.path}
                  className={`nav-link text-light rounded ${location.pathname === item.path ? 'bg-primary' : ''}`}
                >
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
          {user && (
            <div className="mt-5 p-2 bg-dark rounded border border-secondary">
              <small className="d-block text-muted">Logged in as</small>
              <strong className="text-light">{user.username}</strong>
              <span className="qc-badge d-block mt-1">{user.account_mode}</span>
            </div>
          )}
        </div>
        <div className="col-md-9 col-lg-10 p-4">
          {children}
        </div>
      </div>
    </div>
  );
}
