document.addEventListener('DOMContentLoaded', () => {
  if (!window.DJSingle || !DJSingle.id) return;
  fetch(`${DJSingle.apiUrl}documento/${DJSingle.id}/view`, {
    method: 'POST',
    headers: { 'X-WP-Nonce': DJRepository?.nonce || '' }
  }).catch(() => {});
});