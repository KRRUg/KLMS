const Encore = require('@symfony/webpack-encore');
const Dotenv = require('dotenv-webpack');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     * TODO rename all admin with admin_
     */
    .addEntry('app', './assets/js/app.js')
    .addEntry('admin', './assets/js/admin.js')
    .addEntry('permission', './assets/js/admin/permission.js')
    .addEntry('navigation', './assets/js/admin/navigation.js')
    .addEntry('media', './assets/js/admin/media.js')
    .addEntry('teamsite', './assets/js/admin/teamsite.js')
    .addEntry('sponsor', './assets/js/admin/sponsor.js')
    .addEntry('news', './assets/js/site/news.js')
    .addEntry('seatmap', './assets/js/site/seatmap.js')
    .addEntry('tourney', './assets/js/site/tourney.js')
    .addEntry('shop', './assets/js/site/shopCheckout.js')
    .addEntry('admin_seatmap', './assets/js/admin/seatmap.js')
    .addEntry('admin_tourney', './assets/js/admin/tourney.js')
    .addStyleEntry('email', './assets/css/email.scss')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/js/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    //Need to disable AMD-Loader with imports-loader for DataTables to work
    .addLoader({
        test: /datatables\.net.*/,
        loader: 'imports-loader',
        options: {
            additionalCode:
                "var define = false; /* Disable AMD for misbehaving libraries */",
        },
    })

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()
    //.enableBabelTypeScriptPreset()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    // enable WebPack5 Build Caching (EXPERIMENTAL)
    //.enableBuildCache()

    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|svg|ico)$/
    })

    //copy TineMCE Skin Files
    .copyFiles({
        from: './node_modules/tinymce/skins',
        to: 'skins/[path][name].[ext]'
    })
    //Load .env.local Variables into JS
    .addPlugin(new Dotenv({
        path: './.env.local',
        systemvars: false,
        ignoreStub: true,
    }))
    ;

module.exports = Encore.getWebpackConfig();