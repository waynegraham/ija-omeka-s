const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const scssDirectory = path.join(root, 'asset/scss');
const cssDirectory = path.join(root, 'asset/css');

function buildCss() {
  const sass = require('sass');
  const postcss = require('postcss');
  const autoprefixer = require('autoprefixer');

  fs.mkdirSync(cssDirectory, { recursive: true });
  for (const file of fs.readdirSync(scssDirectory)) {
    if (!file.endsWith('.scss') || file.startsWith('_')) continue;

    const source = path.join(scssDirectory, file);
    const destination = path.join(cssDirectory, file.replace(/\.scss$/, '.css'));
    const compiled = sass.compile(source, { style: 'compressed', sourceMap: false });
    const result = postcss([autoprefixer()]).process(compiled.css, {
      from: source,
      to: destination,
      map: false,
    });
    fs.writeFileSync(destination, result.css);
    for (const warning of result.warnings()) console.warn(warning.toString());
    console.log(`Built ${path.relative(root, destination)}`);
  }
}

function copyVendor() {
  const destination = path.join(root, 'asset/js/vendor/bootstrap');
  const bootstrap = path.dirname(require.resolve('bootstrap/package.json'));
  const popper = path.dirname(require.resolve('@popperjs/core/package.json'));
  fs.mkdirSync(destination, { recursive: true });

  for (const file of [
    'bootstrap.js', 'bootstrap.js.map', 'bootstrap.min.js', 'bootstrap.min.js.map',
    'bootstrap.bundle.js', 'bootstrap.bundle.js.map',
    'bootstrap.bundle.min.js', 'bootstrap.bundle.min.js.map',
  ]) {
    fs.copyFileSync(path.join(bootstrap, 'dist/js', file), path.join(destination, file));
  }
  for (const file of ['popper.min.js', 'popper.min.js.map']) {
    fs.copyFileSync(path.join(popper, 'dist/umd', file), path.join(destination, file));
  }
  console.log('Copied Bootstrap and Popper assets');
}

switch (process.argv[2]) {
  case 'css':
    buildCss();
    break;
  case 'vendor':
    copyVendor();
    break;
  case 'watch': {
    const rebuild = () => {
      try {
        buildCss();
      } catch (error) {
        console.error(error.message);
      }
    };
    rebuild();
    let timer;
    fs.watch(scssDirectory, (event, filename) => {
      if (filename && !filename.endsWith('.scss')) return;
      clearTimeout(timer);
      timer = setTimeout(rebuild, 100);
    });
    console.log('Watching asset/scss; press Ctrl+C to stop');
    break;
  }
  default:
    console.error('Usage: node scripts/assets.js <css|vendor|watch>');
    process.exitCode = 1;
}
