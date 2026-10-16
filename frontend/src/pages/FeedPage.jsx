import React, { useState, useEffect } from 'react';

export default function FeedPage() {
  const [posts, setPosts] = useState([]);
  const [content, setContent] = useState('');

  useEffect(() => {
    fetch('/api/v1/posts/posts/')
      .then((res) => res.json())
      .then((data) => setPosts(Array.isArray(data) ? data : data.results || []))
      .catch(() => setPosts([]));
  }, []);

  const handleCreatePost = (e) => {
    e.preventDefault();
    if (!content) return;
    const token = localStorage.getItem('qc_token');
    fetch('/api/v1/posts/posts/', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Token ${token}`,
      },
      body: JSON.stringify({ content, post_type: 'standard', audience: 'public' }),
    })
      .then((res) => res.json())
      .then((newPost) => {
        setPosts([newPost, ...posts]);
        setContent('');
      });
  };

  return (
    <div>
      <h2 className="fw-bold mb-4">Home Feed</h2>
      <div className="qc-card mb-4">
        <form onSubmit={handleCreatePost}>
          <textarea
            className="form-control bg-dark text-light border-secondary mb-3"
            rows="3"
            placeholder="Share something with QuickChat..."
            value={content}
            onChange={(e) => setContent(e.target.value)}
          ></textarea>
          <button type="submit" className="btn btn-qc-primary">Post Update</button>
        </form>
      </div>

      {posts.map((post) => (
        <div key={post.id} className="qc-card">
          <div className="d-flex justify-content-between align-items-center mb-2">
            <strong>{post.author?.username || 'User'}</strong>
            <small className="text-muted">{new Date(post.created_at).toLocaleTimeString()}</small>
          </div>
          <p>{post.content}</p>
          <div className="d-flex gap-3 text-muted border-top border-secondary pt-2">
            <small>❤️ {post.likes_count || 0} Likes</small>
            <small>💬 {post.comments_count || 0} Comments</small>
          </div>
        </div>
      ))}
    </div>
  );
}
