// Shared keyboard-focus and unavailable-image behavior.
(() => {
  // Core owns query insertion/removal; restore a useful focus position afterwards.
  document.addEventListener('click', event => {
    const button = event.target.closest('#property-queries .add-value, #property-queries .remove-value');
    if (!button) return;
    const field = button.closest('#property-queries');
    const row = button.closest('.value');
    const neighbor = row && (row.previousElementSibling || row.nextElementSibling);
    const count = field.querySelectorAll('.value').length;
    setTimeout(() => {
      if (row && !row.isConnected) {
        (neighbor?.querySelector('.query-property') || field.querySelector('.add-value'))?.focus();
      } else if (!row && field.querySelectorAll('.value').length > count) {
        field.querySelector('.value:last-child .query-property')?.focus();
      }
    }, 0);
  }, true);

  function unavailable(image) {
    const frame = image.closest('.archive-image');
    if (!frame) return;
    image.remove();
    frame.classList.remove('has-image');
  }
  document.addEventListener('error', event => {
    if (event.target instanceof HTMLImageElement) unavailable(event.target);
  }, true);
  function checkImages() {
    document.querySelectorAll('.archive-image img').forEach(image => {
      if (image.complete && image.naturalWidth === 0) unavailable(image);
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkImages, { once: true });
  } else {
    checkImages();
  }
})();
