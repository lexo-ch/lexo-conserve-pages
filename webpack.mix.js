const mix = require('laravel-mix');

mix
  .setPublicPath('dist')
  .browserSync({
    proxy: "http://test.test",
    files: [
      'dist/**/**',
      '**/*.php',
    ],
  });

mix
  .js('resources/scripts/admin-cp.js', 'js')
  .sass('resources/styles/admin-cp.scss', 'css')
  .options({
    processCssUrls: false,
  });

mix
  .version()
  .sourceMaps();
