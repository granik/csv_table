const {
  src, dest, task, parallel, watch, series
} = require('gulp');
const { rollup } = require('gulp-rollup-2');
const { babel } = require('@rollup/plugin-babel');
const commonjs = require('@rollup/plugin-commonjs');
const stripComments = require('gulp-strip-json-comments');
const removeEmptyLines = require('gulp-remove-empty-lines');
const sassCompiler = require('gulp-sass')(
  require('sass')
);
const prefix = require('gulp-autoprefixer');

const jsDist = '../js';
const styleDist = '../css';

const babelOptions = {
  babelHelpers: 'runtime',
  exclude: 'node_modules/**',
  presets: [['@babel/preset-env', {
    useBuiltIns: false,
    modules: 'auto',
  }]],
  plugins: [
    ['@babel/plugin-transform-runtime', {
      runtimeHelpers: true,
    }],
  ],
};

// Build JS.
function js() {
  return src('./js/csv_table.filter.es6.js')
    .pipe(rollup({
        output: {
          file: 'csv_table.filter.js',
          name: 'csv_table.filter',
          format: 'iife',
        },
        plugins: [babel(babelOptions), commonjs()],
      }
    ))
    .pipe(stripComments())
    .pipe(removeEmptyLines())
    .pipe(dest(jsDist));
}

// Build styles.
function sass() {
  return src('./sass/*.s[ac]ss')
    .pipe(sassCompiler({
      errLogToConsole: false,
      onError: err => {
        console.log(err);
      }
    }))
    .pipe(stripComments())
    .pipe(prefix())
    .pipe(dest(styleDist));
}

task('js', js);
task('sass', sass);
task('default', series('js', 'sass'));
task('watch', () => {
  watch('./js/csv_table_filter.es6.js', parallel('js'));
  watch('./sass/**/*.s[ac]ss', parallel('styles'));
});
