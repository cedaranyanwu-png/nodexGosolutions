import React, { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('qc_token') || null);

  useEffect(() => {
    if (token) {
      fetch('/api/v1/accounts/me/', {
        headers: { Authorization: `Token ${token}` },
      })
        .then((res) => (res.ok ? res.json() : null))
        .then((data) => {
          if (data) setUser(data);
          else logout();
        })
        .catch(() => logout());
    }
  }, [token]);

  const login = (data) => {
    localStorage.setItem('qc_token', data.token);
    setToken(data.token);
    setUser(data.user);
  };

  const logout = () => {
    localStorage.removeItem('qc_token');
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, token, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
