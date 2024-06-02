/*
 * admin-suite frontend webpack builder
 */
const Encore = require('@symfony/webpack-encore');

Encore
    // set build path
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    // add entry points
    .addEntry('main-css', './assets/css/main.css')

    // other webpack configs
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
;

module.exports = Encore.getWebpackConfig();
