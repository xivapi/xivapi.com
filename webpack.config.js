let Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/ui/')
    .setPublicPath('/ui')
    .addEntry('battlebar_js', './assets/js/BattleBar/App.js')
    .addStyleEntry('vis', './assets/css/App.scss')
    .addStyleEntry('battlebar_css', './assets/css/BattleBar/App.scss')
    .enableSassLoader(function(options) {}, {
        resolveUrlLoader: false
    })
;

let config = Encore.getWebpackConfig();
config.output.library = 'Dalamud';
config.output.libraryExport = "default";
config.output.libraryTarget = 'var';
module.exports = config;
