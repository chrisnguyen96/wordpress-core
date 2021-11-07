'use strict';

var nameFolderTheme = 'project-name';

var gulp = require('gulp');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');

gulp.task('sass', function() {
    // 1. where is my scss file
    return gulp.src('src/wp-content/themes/'+ nameFolderTheme +'/assets/scss/**/*.scss')
        // 2. pass that file through sass compiler
        .pipe(sass({
            outputStyle: "compressed"
        }).on('error', sass.logError))
        // 3. Autoprefix css for cross browser compatibility
        .pipe(autoprefixer())
        // 4. where do I save the compiled css
        .pipe(gulp.dest('src/wp-content/themes/'+ nameFolderTheme +'/assets/css'))
});

gulp.task('watch', gulp.series(function() {
    gulp.watch('src/wp-content/themes/'+ nameFolderTheme +'/assets/scss/**/*.scss', gulp.series('sass'));
}));

gulp.task('default', gulp.series('watch'));