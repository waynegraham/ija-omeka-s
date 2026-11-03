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
