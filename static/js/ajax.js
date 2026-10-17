/**
 * QuickChat Reusable AJAX Helpers with Django CSRF Token Integration
 */
function getCookie(name) {
  let cookieValue = null;
  if (document.cookie && document.cookie !== '') {
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i].trim();
      if (cookie.substring(0, name.length + 1) === (name + '=')) {
        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
        break;
      }
    }
  }
  return cookieValue;
}

async function qcFetch(url, options = {}) {
  const csrftoken = getCookie('csrftoken');
  const headers = {
    'Content-Type': 'application/json',
    'X-CSRFToken': csrftoken || '',
    ...(options.headers || {})
  };

  const response = await fetch(url, { ...options, headers });
  if (!response.ok) {
    const err = await response.json().catch(() => ({ error: 'Network response error' }));
    throw err;
  }
  return response.json();
}
