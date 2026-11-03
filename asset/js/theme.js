// Missing migrated files must not leave broken-image graphics in the catalogue.
(() => {
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
