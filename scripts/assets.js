const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const scssDirectory = path.join(root, 'asset/scss');
const cssDirectory = path.join(root, 'asset/css');

function buildCss(destinationDirectory = cssDirectory) {
  const sass = require('sass');
  const postcss = require('postcss');
  const autoprefixer = require('autoprefixer');

  fs.mkdirSync(destinationDirectory, { recursive: true });
  for (const file of fs.readdirSync(scssDirectory)) {
    if (!file.endsWith('.scss') || file.startsWith('_')) continue;

    const source = path.join(scssDirectory, file);
    const destination = path.join(destinationDirectory, file.replace(/\.scss$/, '.css'));
    const compiled = sass.compile(source, {
      style: 'compressed', sourceMap: false,
      loadPaths: [path.join(root, 'node_modules')], quietDeps: true,
    });
    const result = postcss([autoprefixer()]).process(compiled.css, {
      from: source,
      to: destination,
      map: false,
    });
    fs.writeFileSync(destination, result.css);
    for (const warning of result.warnings()) console.warn(warning.toString());
    console.log(`Built ${path.basename(destination)}`);
  }
}

function copyVendor(destination = path.join(root, 'asset/js/vendor/bootstrap')) {
  const bootstrap = path.dirname(require.resolve('bootstrap/package.json'));
  const popper = path.dirname(require.resolve('@popperjs/core/package.json'));
  fs.mkdirSync(destination, { recursive: true });

  for (const file of [
    'bootstrap.min.js', 'bootstrap.min.js.map',
  ]) {
    fs.copyFileSync(path.join(bootstrap, 'dist/js', file), path.join(destination, file));
  }
  for (const file of ['popper.min.js', 'popper.min.js.map']) {
    fs.copyFileSync(path.join(popper, 'dist/umd', file), path.join(destination, file));
  }
  console.log('Copied Bootstrap and Popper assets');
}

function buildIcons(destination = path.join(root, 'asset/icons')) {
  const fontawesome = path.dirname(require.resolve('@fortawesome/fontawesome-free/package.json'));
  const icons = {
    search: 'solid/magnifying-glass', plus: 'solid/plus', minus: 'solid/minus',
    private: 'solid/eye-slash', annotation: 'solid/message',
    book: 'solid/book-open', images: 'regular/images', archive: 'solid/box-archive',
    youtube: 'brands/youtube', folder: 'regular/folder',
    'file-text': 'regular/file-lines', image: 'regular/image', file: 'regular/file',
  };
  const symbols = Object.entries(icons).map(([id, source]) => {
    const svg = fs.readFileSync(path.join(fontawesome, 'svgs', `${source}.svg`), 'utf8');
    const match = svg.match(/^<svg[^>]*viewBox="([^"]+)"[^>]*>([\s\S]*)<\/svg>\s*$/);
    if (!match) throw new Error(`Unexpected SVG format for ${source}`);
    return `<symbol id="${id}" viewBox="${match[1]}">${match[2]}</symbol>`;
  });
  fs.mkdirSync(destination, { recursive: true });
  fs.writeFileSync(path.join(destination, 'fontawesome.svg'),
    '<svg xmlns="http://www.w3.org/2000/svg">\n'
    + '<!-- Font Awesome Free icons converted to a symbol sprite; original attribution retained below. -->\n'
    + symbols.join('\n') + '\n</svg>\n');
  fs.copyFileSync(path.join(fontawesome, 'LICENSE.txt'), path.join(destination, 'LICENSE.txt'));
  console.log('Built local Font Awesome SVG sprite');
}

module.exports = { buildCss, copyVendor, buildIcons };

if (require.main === module) switch (process.argv[2]) {
  case 'css':
    buildCss();
    break;
  case 'vendor':
    copyVendor();
    break;
  case 'icons':
    buildIcons();
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
    console.error('Usage: node scripts/assets.js <css|vendor|icons|watch>');
    process.exitCode = 1;
}
