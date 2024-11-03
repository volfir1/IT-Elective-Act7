// gulpfile.js
import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import sourcemaps from 'gulp-sourcemaps';
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS from 'gulp-clean-css';
import rename from 'gulp-rename';

const sass = gulpSass(dartSass);

// Define paths for different page styles
const paths = {
    styles: {
        src: 'src/scss/pages/*.scss',  // This will catch individual page SCSS files
        components: 'src/scss/components/*.scss',
        watch: 'src/scss/**/*.scss',   // Watch all SCSS files for changes
        dest: 'public/css'
    }
};

// Compile individual page SCSS files
function pageStyles() {
    return gulp.src(paths.styles.src)
        .pipe(sourcemaps.init())
        .pipe(sass({
            includePaths: ['src/scss'],
            outputStyle: 'expanded'
        }).on('error', sass.logError))
        .pipe(autoprefixer({
            cascade: false,
            grid: 'autoplace'
        }))
        // Save unminified version
        .pipe(gulp.dest(paths.styles.dest))
        // Save minified version
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.styles.dest));
}

// Watch for changes
function watch() {
    gulp.watch(paths.styles.watch, pageStyles);
}

// Export tasks
export { pageStyles, watch };
export default gulp.series(pageStyles, watch);