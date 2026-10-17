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
      <h2 className="fw-bold mb-4 text-primary">Home Social Feed</h2>
      <div className="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <form onSubmit={handleCreatePost}>
          <textarea
            className="form-control mb-3 border-light-subtle"
            rows="3"
            placeholder="Share an update with QuickChat..."
            value={content}
            onChange={(e) => setContent(e.target.value)}
          ></textarea>
          <button type="submit" className="btn btn-primary fw-bold px-4 rounded-3">
            Post Update
          </button>
        </form>
      </div>

      {posts.map((post) => (
        <div key={post.id} className="card border-0 shadow-sm rounded-4 p-4 mb-3 bg-white">
          <div className="d-flex justify-content-between align-items-center mb-2">
            <strong className="text-primary fs-5">{post.author?.username || 'User'}</strong>
            <small className="text-muted">{new Date(post.created_at).toLocaleTimeString()}</small>
          </div>
          <p className="lead text-secondary mb-3">{post.content}</p>
          <div className="d-flex gap-4 text-muted border-top pt-2">
            <small className="fw-semibold">❤️ {post.likes_count || 0} Likes</small>
            <small className="fw-semibold">💬 {post.comments_count || 0} Comments</small>
          </div>
        </div>
      ))}
    </div>
  );
}
