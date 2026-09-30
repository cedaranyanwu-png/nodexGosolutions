/* Client onboarding reuses the canonical auth/register API so accounts do not fork. */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#clientRegisterForm');
  if (!form) return;
  const message = document.createElement('div'); message.id = 'clientRegisterMessage'; form.prepend(message);
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('[type="submit"]'); button.disabled = true;
    const value = (selector) => form.querySelector(selector)?.value.trim() || '';
    try {
      const result = await Nodex.api('auth/register/', { method: 'POST', body: JSON.stringify({ first_name: value('#clientFirstName'), last_name: value('#clientLastName'), email: value('#clientEmail'), password: form.querySelector('#clientPassword').value, account_type: form.querySelector('[name="clientAccountType"]:checked')?.value || 'business' }) });
      Nodex.showMessage('#clientRegisterMessage', `${result.message} <a href="${result.verify_link}" class="alert-link">Verify email now</a>`);
      form.reset();
    } catch (error) { Nodex.showMessage('#clientRegisterMessage', error.message, 'danger'); }
    finally { button.disabled = false; }
  });
});
