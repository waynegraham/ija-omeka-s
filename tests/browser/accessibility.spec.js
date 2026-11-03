const { test, expect } = require('@playwright/test');

function contrast(a, b) {
  const luminance = color => {
    const [r, g, b] = color.match(/[\d.]+/g).slice(0, 3).map(Number).map(v => {
      v /= 255;
      return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4;
    });
    return .2126 * r + .7152 * g + .0722 * b;
  };
  const values = [luminance(a), luminance(b)].sort((a, b) => b - a);
  return (values[0] + .05) / (values[1] + .05);
}

test('advanced search gives added query joiners accessible names', async ({ page }) => {
  await page.goto('/search.html');
  await page.getByRole('button', { name: 'Add new value' }).click();
  const joiner = page.locator('.joiner').last();
  await expect(joiner).toBeVisible();
  await expect(joiner).toHaveAccessibleName('Combine with previous query');
});

test('focus indicators, input boundaries and footer hover links have sufficient contrast', async ({ page }) => {
  await page.goto('/home.html');
  await page.keyboard.press('Tab');
  const focus = await page.locator('.skip-link').evaluate(el => {
    const s = getComputedStyle(el);
    return { color: s.outlineColor, background: s.backgroundColor };
  });
  expect.soft(contrast(focus.color, focus.background)).toBeGreaterThanOrEqual(3);
  const input = await page.locator('#home-search input').evaluate(el => {
    const s = getComputedStyle(el);
    return { border: s.borderTopColor, background: s.backgroundColor };
  });
  expect.soft(contrast(input.border, input.background)).toBeGreaterThanOrEqual(3);
  const link = page.locator('footer a').first();
  await link.hover();
  const footer = await link.evaluate(el => ({ color: getComputedStyle(el).color, background: getComputedStyle(el.closest('footer')).backgroundColor }));
  expect.soft(contrast(footer.color, footer.background)).toBeGreaterThanOrEqual(4.5);
});

for (const path of ['home', 'legacy-home', 'page', 'browse', 'search', 'results', 'item', 'exhibit']) {
  test(`${path} reflows at 320 CSS pixels with user text spacing`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto(`/${path}.html`);
    if (path === 'search') await page.getByRole('button', { name: 'Add new value' }).click();
    await page.addStyleTag({ content: '* { line-height:1.5 !important; letter-spacing:.12em !important; word-spacing:.16em !important; } p { margin-bottom:2em !important; }' });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    if (['search', 'exhibit'].includes(path)) await page.screenshot({ path: testInfo.outputPath('text-spacing-320.png'), fullPage: true });
  });
}

test('image dialog contains keyboard focus and returns it when dismissed', async ({ page }) => {
  await page.goto('/exhibit.html');
  const trigger = page.locator('.exhibit-lightbox-trigger').first();
  await trigger.focus();
  await page.keyboard.press('Enter');
  const dialog = page.getByRole('dialog', { name: 'Image viewer' });
  await expect(dialog).toBeVisible();
  for (let i = 0; i < 8; i++) {
    await page.keyboard.press('Tab');
    // Native dialogs may cycle through browser chrome (activeElement becomes body),
    // but must never send focus to a control in the underlying page.
    expect(await dialog.evaluate(el => el.contains(document.activeElement) || document.activeElement === document.body)).toBe(true);
  }
  await page.keyboard.press('Escape');
  await expect(trigger).toBeFocused();
});

test('adding and removing advanced queries keeps keyboard focus in the form', async ({ page }) => {
  await page.goto('/search.html');
  const add = page.getByRole('button', { name: 'Add new value' });
  await add.focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('.query-property').last()).toBeFocused();
  await page.getByRole('button', { name: 'Remove value', exact: true }).last().focus();
  await page.keyboard.press('Enter');
  await expect(page.locator('.query-property').first()).toBeFocused();
});

for (const path of ['home', 'search', 'item', 'exhibit']) {
  for (const width of [960, 1280]) {
    test(`${path} supports 200 percent text resizing at ${width}px`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await page.goto(`/${path}.html`);
      await page.addStyleTag({ content: 'html { font-size: 200% !important; }' });
      await page.evaluate(() => document.fonts.ready);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
      await expect(page.locator('main')).toBeVisible();
    });
  }
}
