(() => {
  function initialize() {
    const media = window.matchMedia('(min-width: 768px)');
    const chapters = [...document.querySelectorAll('.exhibit-chapters')];
    function sticky() {
      chapters.forEach(details => {
        const sidebar = details.closest('.page-nav');
        if (sidebar) sidebar.classList.toggle('is-sticky', media.matches && sidebar.getBoundingClientRect().height < window.innerHeight - 32);
      });
    }
    function setChapterMode() {
      chapters.forEach(details => { details.open = media.matches; });
      sticky();
    }
    setChapterMode();
    media.addEventListener('change', setChapterMode);
    window.addEventListener('resize', sticky, { passive: true });
    if (typeof ResizeObserver === 'function') {
      const observer = new ResizeObserver(sticky);
      chapters.forEach(details => observer.observe(details));
    }

    const dialog = document.querySelector('.exhibit-image-viewer');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    // Progressive enhancement: original item links remain usable without JS.
    document.querySelectorAll('.exhibit-page .media-embed').forEach((gallery, groupIndex) => {
      gallery.dataset.exhibitGallery = `media-${groupIndex}`;
      gallery.querySelectorAll('.resource').forEach(resource => {
        const image = resource.querySelector('img');
        const anchor = image && image.closest('a');
        const record = [...resource.querySelectorAll('a[href]')].find(a => /\/item\/\d+(?:[/?#]|$)/.test(a.getAttribute('href')));
        if (!image || !anchor || !resource.contains(anchor) || !record || anchor.querySelectorAll('img').length !== 1) return;
        const caption = resource.querySelector('.caption') || resource.querySelector('h3');
        const title = (caption && caption.textContent.trim()) || image.alt || dialog.dataset.enlarge;
        image.alt = image.alt || title;
        const recordHref = record.getAttribute('href');
        const hasSeparateRecordLink = [...resource.querySelectorAll('a[href]')].some(link => link !== anchor && link.getAttribute('href') === recordHref);
        if (!hasSeparateRecordLink) {
          const persistent = document.createElement('a');
          persistent.href = recordHref;
          persistent.className = 'exhibit-record-link';
          persistent.textContent = dialog.dataset.recordLabel;
          resource.append(persistent);
        }
        anchor.classList.add('exhibit-photo-image');
        anchor.dataset.lightboxSrc = image.src.replace('/files/square/', '/files/large/').replace('/files/medium/', '/files/large/');
        anchor.dataset.lightboxCaption = title;
        anchor.dataset.recordUrl = record.getAttribute('href');
        const frame = document.createElement('span');
        frame.className = 'archive-image has-image';
        image.before(frame);
        frame.append(image);
        const fallback = document.createElement('span');
        fallback.className = 'image-unavailable';
        fallback.textContent = dialog.dataset.unavailable;
        frame.append(fallback);
        const label = document.createElement('span');
        label.className = 'image-enlarge-label';
        label.textContent = dialog.dataset.enlarge;
        anchor.append(label);
      });
    });
    document.querySelectorAll('.exhibit-photo-image[data-lightbox-src]').forEach(anchor => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'exhibit-photo-image exhibit-lightbox-trigger';
      for (const [key, value] of Object.entries(anchor.dataset)) button.dataset[key] = value;
      button.setAttribute('aria-label', `${dialog.dataset.enlarge}: ${anchor.dataset.lightboxCaption}`);
      while (anchor.firstChild) button.append(anchor.firstChild);
      anchor.replaceWith(button);
      const image = button.querySelector('img');
      function failed() {
        button.disabled = true;
        image?.remove();
        button.querySelector('.archive-image')?.classList.remove('has-image');
        button.querySelector('.image-enlarge-label')?.remove();
      }
      if (!image || (image.complete && image.naturalWidth === 0)) failed();
      else image.addEventListener('error', failed, { once: true });
    });

    const image = dialog.querySelector('img');
    const caption = dialog.querySelector('figcaption');
    const status = dialog.querySelector('[role="status"]');
    const position = dialog.querySelector('.exhibit-viewer-position');
    const record = dialog.querySelector('.exhibit-viewer-record');
    const previous = dialog.querySelector('[data-viewer="previous"]');
    const next = dialog.querySelector('[data-viewer="next"]');
    const close = dialog.querySelector('[data-viewer="close"]');
    let origin = null;
    let group = [];
    let index = 0;
    function showPhoto() {
      const photo = group[index];
      caption.textContent = photo.dataset.lightboxCaption;
      image.alt = photo.dataset.lightboxCaption;
      image.hidden = true;
      status.textContent = dialog.dataset.loading;
      record.hidden = !photo.dataset.recordUrl;
      if (photo.dataset.recordUrl) record.href = photo.dataset.recordUrl;
      position.textContent = `${index + 1} / ${group.length}`;
      previous.disabled = index === 0;
      next.disabled = index === group.length - 1;
      image.onload = () => { image.hidden = false; status.textContent = ''; };
      image.onerror = () => { image.hidden = true; status.textContent = dialog.dataset.error; };
      image.src = photo.dataset.lightboxSrc;
      if ((document.activeElement === next && next.disabled) || (document.activeElement === previous && previous.disabled)) close.focus();
    }
    document.addEventListener('click', event => {
      const trigger = event.target.closest('.exhibit-lightbox-trigger');
      if (!trigger || trigger.disabled) return;
      origin = trigger;
      const gallery = trigger.closest('[data-exhibit-gallery]');
      group = [...(gallery || document).querySelectorAll('.exhibit-lightbox-trigger:not(:disabled)')];
      index = group.indexOf(trigger);
      if (index < 0) return;
      showPhoto();
      dialog.showModal();
      document.body.classList.add('exhibit-viewer-open');
      close.focus();
    });
    previous.addEventListener('click', () => { if (index > 0) { index--; showPhoto(); } });
    next.addEventListener('click', () => { if (index < group.length - 1) { index++; showPhoto(); } });
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft') { event.preventDefault(); previous.click(); }
      if (event.key === 'ArrowRight') { event.preventDefault(); next.click(); }
    });
    dialog.addEventListener('close', () => {
      document.body.classList.remove('exhibit-viewer-open');
      if (origin && origin.isConnected && !origin.disabled) origin.focus();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
