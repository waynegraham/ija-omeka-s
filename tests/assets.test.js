const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { test } = require('node:test');
const sass = require('sass');

const root = path.resolve(__dirname, '..');

test('theme styles compile without Sass deprecations', () => {
  const warnings = [];
  const result = sass.compile(path.join(root, 'asset/scss/main.scss'), {
    style: 'compressed',
    logger: { warn: message => warnings.push(message) },
  });
  assert.ok(result.css.includes('.title-bar'));
  assert.deepEqual(warnings, []);
});

test('asset check detects stale and missing files without rewriting them', () => {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'ija-assets-test-'));
  const run = (script, ...args) => spawnSync(process.execPath, [script, ...args], {
    cwd: directory, encoding: 'utf8',
  });
  try {
    fs.cpSync(path.join(root, 'scripts'), path.join(directory, 'scripts'), { recursive: true });
    fs.cpSync(path.join(root, 'asset/scss'), path.join(directory, 'asset/scss'), { recursive: true });
    fs.symlinkSync(path.join(root, 'node_modules'), path.join(directory, 'node_modules'), 'junction');
    for (const command of ['css', 'vendor']) {
      const result = run('scripts/assets.js', command);
      assert.equal(result.status, 0, result.stderr);
    }
    let result = run('scripts/check-assets.js');
    assert.equal(result.status, 0, result.stdout + result.stderr);

    const css = path.join(directory, 'asset/css/main.css');
    fs.appendFileSync(css, '\n.stale { color: red; }');
    const stale = fs.readFileSync(css, 'utf8');
    result = run('scripts/check-assets.js');
    assert.equal(result.status, 1);
    assert.match(result.stdout + result.stderr, /main\.css/);
    assert.equal(fs.readFileSync(css, 'utf8'), stale);

    fs.unlinkSync(path.join(directory, 'asset/js/vendor/bootstrap/popper.min.js'));
    result = run('scripts/check-assets.js');
    assert.equal(result.status, 1);
    assert.match(result.stdout + result.stderr, /popper\.min\.js/);
  } finally {
    fs.rmSync(directory, { recursive: true, force: true });
  }
});
