/* NodexPlatform shared browser layer.
 * Page-specific scripts call these helpers so API contracts stay in one place.
 */
(() => {
  const api = (path, options = {}) => fetch(`/python/api/${path}`, {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  }).then(async (response) => {
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'The request could not be completed.');
    return payload;
  });

  const showMessage = (target, message, type = 'success') => {
    const box = document.querySelector(target);
    if (!box) return;
    box.innerHTML = `<div class="alert alert-${type} py-2 small" role="alert">${message}</div>`;
  };

  const initials = (user) => `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`.toUpperCase() || 'NX';
  const paintUser = (user) => {
    document.querySelectorAll('[data-user-name]').forEach((el) => { el.textContent = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User'; });
    document.querySelectorAll('[data-user-email]').forEach((el) => { el.textContent = user.email || ''; });
    document.querySelectorAll('[data-user-initials]').forEach((el) => { el.textContent = initials(user); });
  };

  window.Nodex = { api, showMessage, paintUser };

  document.addEventListener('click', async (event) => {
    const logout = event.target.closest('[data-action="logout"]');
    if (!logout) return;
    event.preventDefault();
    try { await api('auth/logout/', { method: 'POST', body: '{}' }); } finally { window.location.href = '/login/'; }
  });

  const quickForm = document.querySelector('#quickRequestForm');
  if (quickForm) {
    quickForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submit = quickForm.querySelector('[type="submit"]');
      const selected = document.querySelector('.profile-result.selected')?.dataset.profile || '';
      const payload = {
        profile: selected,
        title: document.querySelector('#requestTitle')?.value.trim(),
        description: document.querySelector('#requestDescription')?.value.trim(),
        budget_min: document.querySelector('#budgetMin')?.value || '',
        budget_max: document.querySelector('#budgetMax')?.value || '',
        deadline: document.querySelector('#requestDeadline')?.value || '',
        email: document.querySelector('#requestEmail')?.value.trim(),
      };
      submit.disabled = true;
      try {
        const result = await api('requests/create/', { method: 'POST', body: JSON.stringify(payload) });
        showMessage('#requestMessage', result.message || 'Request sent successfully.');
        quickForm.reset();
      } catch (error) { showMessage('#requestMessage', error.message, 'danger'); }
      finally { submit.disabled = false; }
    });
  }

  document.querySelectorAll('.profile-result').forEach((card) => card.addEventListener('click', () => {
    document.querySelectorAll('.profile-result').forEach((item) => item.classList.remove('selected'));
    card.classList.add('selected');
    const name = card.querySelector('h5')?.textContent || 'Selected profile';
    const type = card.querySelector('.badge')?.textContent || 'Talent';
    const selectedName = document.querySelector('#selectedName');
    const selectedType = document.querySelector('#selectedType');
    if (selectedName) selectedName.textContent = name;
    if (selectedType) selectedType.textContent = `${type} selected`;
  }));
})();
