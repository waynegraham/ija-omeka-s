const { test, expect } = require('@playwright/test');

async function screenshot(page, testInfo, name) {
  const path = testInfo.outputPath(`${name}.png`);
  await page.screenshot({ path, fullPage: true });
  await testInfo.attach(name, { path, contentType: 'image/png' });
}

test.beforeEach(async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`); });
  page.themeErrors = errors;
  // Fixtures are deliberately independent of remote services, including Google Fonts.
  await page.route(/^https?:\/\/(?!127\.0\.0\.1:8766)/, route => route.abort());
});

test.afterEach(async ({ page }) => {
  expect(page.themeErrors).toEqual([]);
});

test('sidebar and viewport-width media gallery stay within the responsive layout', async ({ page }, testInfo) => {
  await page.goto('/page.html');
  const gallery = page.locator('.media-embed');
  await expect(gallery).toHaveCSS('display', 'grid');
  const nav = await page.locator('.page-nav').boundingBox();
  const content = await page.locator('.site-page-content').boundingBox();
  if (page.viewportSize().width < 768) {
    expect(content.y).toBeGreaterThanOrEqual(nav.y + nav.height - 1);
  } else {
    expect(Math.abs(content.y - nav.y)).toBeLessThan(2);
    expect(content.x).toBeGreaterThanOrEqual(nav.x + nav.width - 1);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await screenshot(page, testInfo, 'page-layout');
});

test('browse thumbnails remain visible and titles are plain text', async ({ page }, testInfo) => {
  await page.goto('/browse.html');
  await expect(page.getByRole('heading', { name: 'Browse the collection', exact: true })).toBeVisible();
  await expect(page.locator('.browse-thumbnail img').first()).toBeVisible();
  await expect(page.locator('.browse-card').last().locator('.browse-thumbnail')).toHaveCount(0);
  await expect(page.locator('.browse-card h3').first()).toContainText('Archive <title>');
  await expect(page.locator('.browse-card h3 title')).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await screenshot(page, testInfo, 'browse-layout');
});

test('annotation disclosure can be opened, closed and reopened using the keyboard', async ({ page }, testInfo) => {
  await page.goto('/item.html');
  const summary = page.locator('.value-annotation summary');
  const annotation = page.locator('.annotation');
  await expect(annotation).toBeHidden();
  await summary.focus();
  await page.keyboard.press('Enter');
  await expect(annotation).toBeVisible();
  await page.keyboard.press('Space');
  await expect(annotation).toBeHidden();
  await expect(summary).toBeVisible();
  await page.keyboard.press('Enter');
  await expect(annotation).toBeVisible();
  const icon = await page.locator('.item-title-icon').boundingBox();
  const heading = await page.locator('.item-title-text').boundingBox();
  expect(heading.x).toBeGreaterThanOrEqual(icon.x + icon.width - 1);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await screenshot(page, testInfo, 'item-layout');
});

test('navigation, skip link and search work with keyboard controls', async ({ page }) => {
  await page.goto('/page.html');
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page.locator('main')).toBeFocused();
  if (page.viewportSize().width < 992) {
    await page.getByRole('button', { name: 'Toggle navigation' }).click();
  }
  const menu = page.getByRole('button', { name: 'Explore', exact: true });
  await menu.focus();
  await page.keyboard.press('Space');
  await expect(menu).toHaveAttribute('aria-expanded', 'true');
  await page.keyboard.press('ArrowDown');
  await expect(page.locator('.dropdown-menu.show a').first()).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(menu).toHaveAttribute('aria-expanded', 'false');
  await expect(menu).toBeFocused();
  await page.getByRole('searchbox', { name: 'Search', exact: true }).fill('archive books');
  const icon = page.locator('#search-form svg');
  expect((await icon.boundingBox()).width).toBeLessThan(40);
  await page.getByRole('button', { name: 'Search', exact: true }).click();
  await expect(page).toHaveURL(/\/items\/search\?fulltext_search=archive\+books$/);
});

test('advanced search adds and removes indexed query rows', async ({ page }) => {
  await page.goto('/search.html');
  const rows = page.locator('#property-queries .value');
  await expect(rows).toHaveCount(1);
  await page.getByRole('button', { name: 'Add new value' }).click();
  await expect(rows).toHaveCount(2);
  const names = await page.locator('.query-text').evaluateAll(inputs => inputs.map(input => input.name));
  expect(new Set(names).size).toBe(2);
  await page.getByRole('button', { name: 'Remove value', exact: true }).last().click();
  await expect(rows).toHaveCount(1);
});


test('homepage has a split hero, ordered highlights and functional search', async ({ page }, testInfo) => {
  await page.goto('/home.html');
  await expect(page.locator('.archive-hero-image')).toHaveAttribute('href', '/items/16426');
  await expect(page.locator('.archive-hero-object figcaption a')).toHaveAttribute('href', '/items/16426');
  await expect(page.locator('.archive-hero-object figcaption')).toContainText('Page 5');
  await expect(page.locator('.archive-hero-object img')).toHaveAttribute('src', /personal-letters-3339-page-5\.jpg$/);
  const intro = await page.locator('.archive-hero-intro').boundingBox();
  const object = await page.locator('.archive-hero-object').boundingBox();
  if (page.viewportSize().width >= 768) {
    expect(Math.abs(intro.y - object.y)).toBeLessThan(2);
    expect(object.x).toBeGreaterThanOrEqual(intro.x + intro.width - 1);
  } else {
    expect(object.y).toBeGreaterThanOrEqual(intro.y + intro.height - 1);
  }
  await expect(page.locator('.archive-object')).toHaveCount(4);
  await expect(page.locator('.archive-object h3').first()).toHaveText('מחזור לראש השנה');
  await expect(page.locator('.parallax')).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await expect.poll(() => page.evaluate(() => Array.from(document.fonts).some(face => face.family.includes('Source Sans Pro') && face.status === 'loaded'))).toBe(true);
  await screenshot(page, testInfo, 'home-layout');
  await page.getByRole('searchbox', { name: 'Search the collection', exact: true }).fill('archive books');
  await page.locator('#home-search').getByRole('button', { name: 'Search', exact: true }).click();
  await expect(page).toHaveURL(/fulltext_search=archive\+books$/);
});

test('stored homepage blocks remain available and broken highlight images have a fallback', async ({ page }) => {
  await page.goto('/legacy-home.html');
  await expect(page.locator('.parallax')).toBeVisible();
  await expect(page.locator('.archive-hero')).toHaveCount(0);
  await page.route('**/thumbnail.svg', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '' }));
  await page.goto('/home.html');
  await page.locator('.archive-highlights').scrollIntoViewIfNeeded();
  await expect(page.locator('.archive-object .image-unavailable').first()).toBeVisible();
  await expect(page.locator('.archive-object img')).toHaveCount(0);
  await expect(page.locator('.archive-object h3 a').first()).toBeVisible();
});

test('sort controls have visible labels and enough room for selected text', async ({ page }) => {
  await page.goto('/browse.html');
  for (const name of ['Sort by', 'Sort order']) {
    const select = page.getByRole('combobox', { name, exact: true });
    await expect(select.locator('..').locator('span')).toBeVisible();
    const fits = await select.evaluate(el => {
      const style = getComputedStyle(el);
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      context.font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
      return context.measureText(el.selectedOptions[0].text).width + parseFloat(style.paddingLeft) + parseFloat(style.paddingRight) + 4 <= el.clientWidth;
    });
    expect(fits).toBe(true);
  }
});

test('search summaries, empty states and mixed-direction titles are readable', async ({ page }, testInfo) => {
  await page.goto('/results.html');
  await expect(page.getByRole('heading', { name: 'Collection records (24)' })).toBeVisible();
  await expect(page.locator('.search-result').first().locator('p')).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await screenshot(page, testInfo, 'search-results');
  await page.goto('/empty-results.html');
  await expect(page.getByText(/Try fewer words/)).toBeVisible();
  await expect(page.locator('.search-empty').getByRole('link', { name: 'Browse the collection' })).toBeVisible();
  await page.goto('/rtl.html');
  await expect(page.locator('.item-primary-title h1')).toHaveCSS('direction', 'rtl');
  await expect(page.locator('.item-title-text h1')).toContainText('IJA 80');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await screenshot(page, testInfo, 'mixed-direction-title');
  await page.goto('/arabic.html');
  await expect(page.locator('.item-primary-title h1')).toHaveCSS('direction', 'rtl');
  await expect(page.locator('.item-primary-title h1')).toContainText('IJA 238');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});


test('exhibit preserves the narrative, responsive chapters and full gallery', async ({ page }, testInfo) => {
  await page.goto('/exhibit.html');
  await expect(page.locator('main h1')).toHaveCount(1);
  await expect(page.locator('.recovery-stage')).toHaveCount(4);
  await expect(page.getByText('On May 6, 2003, discovery narrative with', { exact: false })).toBeVisible();
  const title = await page.locator('.exhibit-introduction').boundingBox();
  const nav = await page.locator('.page-nav').boundingBox();
  expect(title.y + title.height).toBeLessThanOrEqual(nav.y + 1);
  const chapters = page.locator('.exhibit-chapters');
  if (page.viewportSize().width < 768) {
    await expect(chapters).not.toHaveAttribute('open', '');
    await chapters.locator('summary').click();
    await expect(chapters).toHaveAttribute('open', '');
  } else {
    await expect(chapters).toHaveAttribute('open', '');
    await expect(page.locator('.page-nav')).toHaveCSS('position', 'sticky');
  }
  await page.locator('.exhibit-complete-gallery summary').click();
  await expect(page.locator('.exhibit-complete-gallery .exhibit-photo')).toHaveCount(7);
  await expect(page.getByText('Additional photograph retained in full gallery')).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.evaluate(() => scrollTo(0, 0));
  await screenshot(page, testInfo, 'exhibit-layout');
});

test('exhibit image viewer supports next, previous, Escape and focus restoration', async ({ page }) => {
  await page.goto('/exhibit.html');
  const trigger = page.locator('.recovery-story .exhibit-lightbox-trigger').first();
  await trigger.click();
  const dialog = page.getByRole('dialog', { name: 'Image viewer' });
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('figcaption')).toContainText('Flooded basement');
  await page.keyboard.press('ArrowRight');
  await expect(dialog.locator('figcaption')).toContainText('Military entering');
  await page.keyboard.press('ArrowLeft');
  await expect(dialog.locator('figcaption')).toContainText('Flooded basement');
  await page.keyboard.press('Tab');
  expect(await dialog.evaluate(el => el.contains(document.activeElement))).toBe(true);
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(trigger).toBeFocused();
  await page.route('**/failed-image.svg', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '' }));
  await trigger.evaluate(el => { el.dataset.lightboxSrc = '/failed-image.svg'; });
  await trigger.click();
  await expect(dialog.getByRole('status')).toContainText('Image could not be loaded');
  await expect(dialog.getByRole('link', { name: 'View collection record' })).toHaveAttribute('href', '/item/288');
  await dialog.getByRole('button', { name: 'Close image viewer' }).click();
  await expect(trigger).toBeFocused();
});


test('generic exhibit media retains its record link when an image fails', async ({ page }) => {
  await page.route('**/thumbnail.svg', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '' }));
  await page.goto('/generic-exhibit.html');
  await expect(page.getByRole('link', { name: 'View collection record', exact: true })).toHaveAttribute('href', '/item/288');
  await expect(page.locator('.exhibit-lightbox-trigger')).toBeDisabled();
  await expect(page.locator('.image-unavailable')).toBeVisible();
});


test('item view prioritizes the viewer and groups the complete catalogue', async ({ page }, testInfo) => {
  await page.goto('/item.html');
  const viewer = await page.locator('#item-viewer').boundingBox();
  const description = await page.getByText('A description placed below the viewer.', { exact: true }).boundingBox();
  expect(description.y).toBeGreaterThan(viewer.y + viewer.height);
  await expect(page.getByText('A description placed below the viewer.', { exact: true })).toHaveCount(1);
  await expect(page.locator('.item-media-summary')).toHaveText('2 images · 1 PDF');
  await expect(page.locator('#metadata-physical')).not.toHaveAttribute('open', '');
  await screenshot(page, testInfo, 'item-initial-layout');
  await page.getByRole('button', { name: 'Expand all details', exact: true }).click();
  await expect(page.getByText('19.60 cm')).toBeVisible();
  await expect(page.locator('.item-metadata-group:not([open])')).toHaveCount(0);
  await page.getByRole('button', { name: 'Collapse all details', exact: true }).click();
  await expect(page.locator('.item-metadata-group[open]')).toHaveCount(0);
  await page.getByText('Original images (2)', { exact: true }).click();
  await expect(page.getByRole('link', { name: 'Image 2 of 2', exact: true })).toHaveAttribute('href', '/original/scan_E.jpg');
  await expect(page.getByText('scan_E.jpg', { exact: true })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await page.evaluate(() => scrollTo(0, 0));
  await screenshot(page, testInfo, 'item-research-layout');
});

test('item tools copy real links and restore the actual results context', async ({ page }) => {
  await page.addInitScript(() => {
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async text => { window.copiedText = text; } } });
  });
  await page.goto('/browse.html?fulltext_search=archive&sort_by=date&page=2');
  await page.locator('.browse-card a').first().click();
  await expect(page.locator('.item-back-results')).toHaveAttribute('href', 'http://127.0.0.1:8766/browse.html?fulltext_search=archive&sort_by=date&page=2');
  await page.getByRole('button', { name: 'Copy record link', exact: true }).click();
  expect(await page.evaluate(() => window.copiedText)).toBe('http://127.0.0.1:8766/item.html');
  await expect(page.getByRole('status').filter({ hasText: 'Record link copied' })).toBeVisible();
  await page.getByText('Cite this item', { exact: true }).click();
  await page.getByRole('button', { name: 'Copy citation', exact: true }).click();
  expect(await page.evaluate(() => window.copiedText)).toContain('IJA 123');
  await page.locator('.item-back-results').click();
  await expect(page).toHaveURL(/browse\.html\?fulltext_search=archive&sort_by=date&page=2$/);
});


test('copy tools provide a manual fallback and reject external results context', async ({ page }) => {
  await page.addInitScript(() => {
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async () => { throw new Error('Permission denied'); } } });
    sessionStorage.setItem('ija-result-context', JSON.stringify({ url: 'https://example.org/untrusted', scope: '/', recordPath: '/item.html' }));
  });
  await page.goto('/item.html');
  await expect(page.locator('.item-back-results')).toBeHidden();
  await page.getByRole('button', { name: 'Copy record link', exact: true }).click();
  await expect(page.getByRole('textbox', { name: 'Record link', exact: true })).toHaveValue('http://127.0.0.1:8766/item.html');
  await expect(page.getByRole('status').filter({ hasText: 'Copy is unavailable' })).toBeVisible();
});


test('metadata borders align at intermediate widths', async ({ page }) => {
  for (const width of [575, 576, 600, 767, 768]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/item.html');
    const row = await page.locator('#metadata-description dt').first().evaluate(dt => {
      const dd = dt.nextElementSibling;
      return { label: dt.getBoundingClientRect().toJSON(), value: dd.getBoundingClientRect().toJSON(), border: getComputedStyle(dt).borderBottomWidth };
    });
    if (width >= 576) {
      expect(row.border).toBe('1px');
      expect(Math.abs(row.label.bottom - row.value.bottom)).toBeLessThan(1);
    } else {
      expect(row.border).toBe('0px');
      expect(row.value.top).toBeGreaterThanOrEqual(row.label.bottom);
    }
  }
});

test('printing opens catalogue groups and restores their previous state', async ({ page }) => {
  await page.goto('/item.html');
  const groups = page.locator('.item-metadata-group');
  const state = await groups.evaluateAll(nodes => nodes.map(node => node.open));
  await page.emulateMedia({ media: 'print' });
  await page.evaluate(() => window.dispatchEvent(new Event('beforeprint')));
  await expect(page.locator('.item-metadata-group:not([open])')).toHaveCount(0);
  await expect(page.getByText('19.60 cm')).toBeVisible();
  await expect(page.locator('.site-header')).toBeHidden();
  await expect(page.locator('#ija_metadata')).toBeVisible();
  await page.evaluate(() => window.dispatchEvent(new Event('afterprint')));
  await page.emulateMedia({ media: 'screen' });
  expect(await groups.evaluateAll(nodes => nodes.map(node => node.open))).toEqual(state);
});
