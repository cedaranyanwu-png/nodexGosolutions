import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function MainLayout({ children }) {
  const location = useLocation();
  const { user } = useAuth();

  const navItems = [
    { label: 'Home', path: '/' },
    { label: 'Explore', path: '/explore' },
    { label: 'Reels', path: '/reels' },
    { label: 'Messages', path: '/messages' },
    { label: 'Communities', path: '/communities' },
    { label: 'Pro Rooms', path: '/rooms' },
    { label: 'Media Studio', path: '/media-studio' },
    { label: 'Creator Hub', path: '/creator-hub' },
    { label: 'Superadmin', path: '/admin-panel' },
  ];

  return (
    <div className="bg-light min-vh-100">
      {/* Instagram-Inspired Top Navbar */}
      <nav className="navbar navbar-expand-lg navbar-white bg-white border-bottom sticky-top shadow-sm py-2">
        <div className="container">
          <Link to="/" className="navbar-brand fw-bold text-primary fs-4 d-flex align-items-center gap-2">
            <span className="bg-primary text-white px-2 py-1 rounded fs-6">QC</span> QuickChat
          </Link>
          <div className="d-flex align-items-center gap-3 ms-auto">
            <Link to="/messages" className="btn btn-outline-primary btn-sm rounded-pill fw-semibold">
              ✉ Messages
            </Link>
            <Link to="/creator-hub" className="btn btn-primary btn-sm rounded-pill fw-semibold">
              + Create
            </Link>
            {user && (
              <span className="badge bg-primary text-white text-capitalize">{user.username}</span>
            )}
          </div>
        </div>
      </nav>

      {/* Responsive Main Layout Container */}
      <div className="container py-4">
        <div className="row">
          {/* Desktop Left Navigation Sidebar */}
          <div className="col-md-3 col-lg-2 bg-white border rounded-4 p-3 d-none d-md-block shadow-sm">
            <ul className="nav nav-pills flex-column gap-1">
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
          </div>

          {/* Main Content Area */}
          <div className="col-md-9 col-lg-10">
            {children}
          </div>
        </div>
      </div>

      {/* Fixed Mobile Bottom Navigation Bar */}
      <nav className="navbar fixed-bottom navbar-light bg-white border-top d-md-none py-2 shadow">
        <div className="container d-flex justify-content-around">
          {navItems.slice(0, 5).map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={`nav-link text-center fw-bold small ${
                location.pathname === item.path ? 'text-primary' : 'text-secondary'
              }`}
            >
              {item.label}
            </Link>
          ))}
        </div>
      </nav>
    </div>
  );
}
