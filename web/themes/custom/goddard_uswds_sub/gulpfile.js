'use strict';
 
var gulp = require('gulp');
var	sass = require('gulp-sass');
var	watch = require('gulp-watch');
 
gulp.task('sass', function () {
  return gulp.src('assets/styles/scss/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('assets/styles/css'));
});
 
gulp.task('watch', function () {
  gulp.watch('assets/styles/scss/*.scss', ['sass']);
});

gulp.task('default');