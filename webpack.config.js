let Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/ui/')
    .setPublicPath('/ui')
    .addStyleEntry('vis', './assets/css/App.scss')
    .enableSassLoader(function(options) {}, {
        resolveUrlLoader: false
    })
;

let config = Encore.getWebpackConfig();
config.output.library = 'Dalamud';
config.output.libraryExport = "default";
config.output.libraryTarget = 'var';
module.exports = config;
