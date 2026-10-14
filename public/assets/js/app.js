document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement) || form.dataset.submitting === 'true') return;
  if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  if (!form.hasAttribute('data-submit-label') && form.method.toLowerCase() === 'get') return;
  form.dataset.submitting = 'true';
  const button = form.querySelector('button[type="submit"]');
  if (button instanceof HTMLButtonElement) {
    button.disabled = true;
    button.dataset.originalLabel = button.textContent || '';
    button.textContent = form.dataset.submitLabel || 'Memproses…';
  }
});

document.querySelectorAll('[data-image-input]').forEach((input) => {
  if (!(input instanceof HTMLInputElement)) return;
  input.addEventListener('change', () => {
    const preview = input.closest('label')?.querySelector('[data-image-preview]');
    const file = input.files?.[0];
    if (!(preview instanceof HTMLImageElement) || !file) return;
    preview.src = URL.createObjectURL(file);
    preview.onload = () => URL.revokeObjectURL(preview.src);
  });
});
