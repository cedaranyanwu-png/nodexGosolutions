import React from 'react';
import { Routes, Route } from 'react-router-dom';
import MainLayout from './layouts/MainLayout';
import FeedPage from './pages/FeedPage';
import ExplorePage from './pages/ExplorePage';
import ReelsPage from './pages/ReelsPage';
import ProfilePage from './pages/ProfilePage';
import MessagesPage from './pages/MessagesPage';
import AdminPage from './pages/AdminPage';
import GenericPage from './pages/GenericPage';

export default function App() {
  return (
    <MainLayout>
      <Routes>
        <Route path="/" element={<FeedPage />} />
        <Route path="/explore" element={<ExplorePage />} />
        <Route path="/reels" element={<ReelsPage />} />
        <Route path="/profile" element={<ProfilePage />} />
        <Route path="/messages" element={<MessagesPage />} />
        <Route path="/calls" element={<GenericPage title="WebRTC Voice & Video Calls" description="Initiate direct or group audio and video calls." />} />
        <Route path="/communities" element={<GenericPage title="Communities" description="Join public and private communities." />} />
        <Route path="/rooms" element={<GenericPage title="Professional Rooms" description="Collaborate in dedicated workspaces with topic channels." />} />
        <Route path="/media-studio" element={<GenericPage title="Media Studio" description="Edit images, trim video clips, and apply creative templates." />} />
        <Route path="/creator-hub" element={<GenericPage title="Creator Dashboard & Verification" description="Track analytics, manage monetization, and apply for verification." />} />
        <Route path="/admin-panel" element={<AdminPage />} />
      </Routes>
    </MainLayout>
  );
}
