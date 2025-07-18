const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
 mix.js('resources/js/app.js', 'public/js')
 .styles([
    'node_modules/@fortawesome/fontawesome-free/css/all.min.css',
    'node_modules/admin-lte/dist/css/adminlte.min.css',
    'resources/css/app.css' // kamu tetap bisa punya file custom
 ], 'public/css/app.css'); // hasil akhirnya satu file besar app.css


mix.copy('node_modules/admin-lte/dist/css/adminlte.min.css', 'public/css/adminlte.min.css');
mix.copyDirectory('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');

// Menggabungkan semua file CSS yang dibutuhkan
// mix.styles([
//     'node_modules/@fortawesome/fontawesome-free/css/all.min.css',
//     'node_modules/admin-lte/dist/css/adminlte.min.css'
// ], 'public/css/app.css');

// mix.scripts([
//     'node_modules/jquery/dist/jquery.min.js',
//     // Kita panggil Popper.js secara manual
//     'node_modules/popper.js/dist/umd/popper.min.js',
//     // Kita panggil bootstrap.min.js (yang kita tahu PASTI ADA)
//     'node_modules/bootstrap/dist/js/bootstrap.min.js',
//     // AdminLTE
//     'node_modules/admin-lte/dist/js/adminlte.min.js',
//     'node_modules/chart.js/dist/chart.umd.min.js'
// ], 'public/js/app.js');

// mix.copyDirectory('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');
// Opsi lain jika menggunakan resource dari Laravel
// mix.js('resources/js/app.js', 'public/js')
//    .postCss('resources/css/app.css', 'public/css', [
//        //
//    ]);