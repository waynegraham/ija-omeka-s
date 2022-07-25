const gulp          = require('gulp');
const browserSync   = require('browser-sync').create();
const $             = require('gulp-load-plugins')();
const autoprefixer  = require('autoprefixer');

const sass = require('gulp-sass')(require('sass'));

const sassPaths = [
  ''
];

function scss() {
  return gulp.src('asset/scss/*.scss')
    .pipe(sass({
      includePaths: sassPaths, 
      outputStyle: 'compressed'
    }).on('error', sass.logError))
    .pipe($.postcss([autoprefixer()]))
    .pipe(gulp.dest('asset/css'))
    .pipe(browserSync.stream());
    // .pipe($.sass({
    //   includePaths: sassPaths,
    //   outputStyle: 'compressed' // if css compressed **file size**
    // })
    //   .on('error', $.sass.logError))
    // .pipe($.postcss([
    //   autoprefixer()
    // ]))
    // .pipe(gulp.dest('asset/css'))
    // .pipe(browserSync.stream());
};

function serve() {
  browserSync.init({
    // server: "./",
    watch: true,
    proxy: "localhost:8888/"
  });

  gulp.watch("**/*.scss", scss);
  gulp.watch("**/*.phtml").on('change', browserSync.reload);
  gulp.watch("**/*.php").on('change', browserSync.reload);
}

function vendor() {
  return gulp.src([
    'node_modules/bootstrap/dist/js/bootstrap.js',
    'node_modules/bootstrap/dist/js/bootstrap.js.map',
    'node_modules/bootstrap/dist/js/bootstrap.min.js',
    'node_modules/bootstrap/dist/js/bootstrap.min.js.map',
    'node_modules/bootstrap/dist/js/bootstrap.bundle.js',
    'node_modules/bootstrap/dist/js/bootstrap.bundle.js.map',
    'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
    'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map'
  ])
  .pipe(gulp.dest('asset/js/vendor/bootstrap'))
  .pipe(browserSync.stream());
}

exports.default = gulp.series(sass, serve, vendor);

gulp.task('scss', scss);
gulp.task('vendor', vendor);
gulp.task('serve', gulp.series('scss', serve));
