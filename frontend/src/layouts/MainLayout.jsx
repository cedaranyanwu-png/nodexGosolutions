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
    <div className="container-fluid bg-light min-vh-100">
      <div className="row">
        {/* Responsive Sidebar Navigation with Blue & White Theme */}
        <div className="col-md-3 col-lg-2 bg-white border-end min-vh-100 p-3 d-none d-md-block shadow-sm">
          <h3 className="fw-bold mb-4 text-primary d-flex align-items-center gap-2">
            <span className="bg-primary text-white px-2 py-1 rounded">QC</span> QuickChat
          </h3>
          <ul className="nav nav-pills flex-column gap-2">
            {navItems.map((item) => {
              const isActive = location.pathname === item.path;
              return (
                <li className="nav-item" key={item.path}>
                  <Link
                    to={item.path}
                    className={`nav-link fw-semibold rounded-3 px-3 py-2 ${
                      isActive ? 'bg-primary text-white' : 'text-dark hover-bg-light'
                    }`}
                  >
                    {item.label}
                  </Link>
                </li>
              );
            })}
          </ul>
          {user && (
            <div className="mt-5 p-3 bg-light rounded-4 border">
              <small className="d-block text-muted">Logged in as</small>
              <strong className="text-primary">{user.username}</strong>
              <span className="badge bg-primary d-block mt-2 text-capitalize">{user.account_mode}</span>
            </div>
          )}
        </div>

        {/* Main Content Viewport */}
        <div className="col-md-9 col-lg-10 p-4">
          {children}
        </div>
      </div>
    </div>
  );
}
