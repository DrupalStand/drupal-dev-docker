'use strict';

var gulp = require('gulp');
var postcss = require('gulp-postcss');
var sass = require('gulp-sass');
var watch = require('gulp-watch');
var sourcemaps = require('gulp-sourcemaps');

var autoprefixer = require('autoprefixer');
var cssnano = require('cssnano');

var scssSrc = './assets/styles/scss';


gulp.task('sass', function () {
  var processors = [
    autoprefixer({browsers: ['last 2 versions']}),
    cssnano()
  ];
  return gulp.src(scssSrc + '/**/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss(processors))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('assets/styles/css'));
});

gulp.task('watch', function () {
  gulp.watch(scssSrc + '/**/*.scss', ['sass']);
});

gulp.task('default');
