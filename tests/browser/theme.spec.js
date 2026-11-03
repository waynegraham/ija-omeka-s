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
