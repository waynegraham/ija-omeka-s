(() => {
  function initialize() {
    const scope = location.pathname.match(/^(.*\/s\/[^/]+)(?:\/|$)/)?.[1] || '/';
    const storageKey = 'ija-result-context';
    function inScope(url) {
      return url.origin === location.origin && (scope === '/' || url.pathname.startsWith(scope + '/'));
    }
    document.addEventListener('click', event => {
      const link = event.target.closest('.browse-card a[href], .search-result a[href]');
      if (!link) return;
      const target = new URL(link.href, location.href);
      if (!inScope(target)) return;
      try {
        sessionStorage.setItem(storageKey, JSON.stringify({ url: location.href, scope, recordPath: target.pathname }));
      } catch (_) { /* Navigation still works when storage is unavailable. */ }
    });
    const back = document.querySelector('.item-back-results');
    if (back) {
      try {
        const context = JSON.parse(sessionStorage.getItem(storageKey));
        const url = context && new URL(context.url);
        if (url && inScope(url) && context.scope === scope && context.recordPath === location.pathname && url.href !== location.href) {
          back.href = url.href;
          back.hidden = false;
        }
      } catch (_) { /* A direct visit has no results context. */ }
    }

    const groups = [...document.querySelectorAll('.item-metadata-group')];
    document.querySelectorAll('.metadata-controls').forEach(controls => { controls.hidden = false; });
    document.querySelectorAll('[data-metadata-action]').forEach(button => button.addEventListener('click', () => {
      groups.forEach(group => { group.open = button.dataset.metadataAction === 'expand'; });
    }));
    let printState = null;
    window.addEventListener('beforeprint', () => {
      if (printState === null) printState = groups.map(group => group.open);
      groups.forEach(group => { group.open = true; });
    });
    window.addEventListener('afterprint', () => {
      if (printState) groups.forEach((group, index) => { group.open = printState[index]; });
      printState = null;
    });

    const tools = document.querySelector('.item-research-tools');
    if (!tools) return;
    const status = tools.querySelector('.item-copy-status');
    const recordUrl = tools.querySelector('#item-record-url');
    if (recordUrl) recordUrl.value = new URL(recordUrl.value, location.href).href;
    tools.querySelectorAll('.item-copy-controls').forEach(control => { control.hidden = false; });
    tools.querySelectorAll('[data-copy-target]').forEach(button => button.addEventListener('click', async () => {
      const field = document.getElementById(button.dataset.copyTarget);
      if (!field) return;
      try {
        if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
        await navigator.clipboard.writeText(field.value);
        status.textContent = button.dataset.copySuccess;
      } catch (_) {
        field.hidden = false;
        field.focus();
        field.select();
        status.textContent = status.dataset.copyError;
      }
    }));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
