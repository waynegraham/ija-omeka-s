const gulp          = require('gulp');
const browserSync   = require('browser-sync').create();
const $             = require('gulp-load-plugins')();
const autoprefixer  = require('autoprefixer');

const sassPaths = [
  ''
];

function sass() {
  return gulp.src('asset/scss/*.scss')
    .pipe($.sass({
      includePaths: sassPaths,
      outputStyle: 'compressed' // if css compressed **file size**
    })
      .on('error', $.sass.logError))
    .pipe($.postcss([
      autoprefixer()
    ]))
    .pipe(gulp.dest('asset/css'))
    .pipe(browserSync.stream());
};

function serve() {
  browserSync.init({
    // server: "./",
    watch: true,
    proxy: "localhost:8888/omeka-s/"
  });

  gulp.watch("**/*.scss", sass);
  gulp.watch("**/*.phtml").on('change', browserSync.reload);
  gulp.watch("**/*.php").on('change', browserSync.reload);
}

exports.default = gulp.series(sass, serve);

gulp.task('sass', sass);
gulp.task('serve', gulp.series('sass', serve));
