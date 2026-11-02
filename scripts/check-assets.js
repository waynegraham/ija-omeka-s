const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { buildCss, copyVendor, buildIcons } = require('./assets');

const root = path.resolve(__dirname, '..');
const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'ija-assets-check-'));
let mismatches = 0;

try {
  buildCss(path.join(temporary, 'css'));
  copyVendor(path.join(temporary, 'js/vendor/bootstrap'));
  buildIcons(path.join(temporary, 'icons'));
  for (const directory of ['css', 'js/vendor/bootstrap', 'icons']) {
    const generated = path.join(temporary, directory);
    const committed = path.join(root, 'asset', directory);
    const files = new Set([
      ...fs.readdirSync(generated),
      ...(fs.existsSync(committed) ? fs.readdirSync(committed) : []),
    ]);
    for (const file of files) {
      const expected = path.join(generated, file);
      const actual = path.join(committed, file);
      if (!fs.existsSync(expected) || !fs.existsSync(actual)
          || !fs.readFileSync(expected).equals(fs.readFileSync(actual))) {
        console.error(`Asset differs: asset/${directory}/${file}`);
        mismatches++;
      }
    }
  }
  if (mismatches) {
    console.error('Run npm run build and commit the generated assets. Remove obsolete generated files.');
    process.exitCode = 1;
  } else {
    console.log('Committed assets match the locked dependencies and SCSS sources');
  }
} finally {
  fs.rmSync(temporary, { recursive: true, force: true });
}
