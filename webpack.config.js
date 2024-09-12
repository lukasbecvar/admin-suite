/* admin-suite frontend webpack builder */
const Encore = require('@symfony/webpack-encore');

Encore
    // set build path
    .setOutputPath('public/assets/')
    .setPublicPath('/assets')

    // register css
    .addEntry('index-css', './assets/css/index.css')

    // register js
    .addEntry('terminal-js', './assets/js/terminal.js')
    .addEntry('todo-manager-js', './assets/js/todo-manager.js')
    .addEntry('user-manager-js', './assets/js/user-manager.js')
    .addEntry('code-highlight-js', './assets/js/code-highlight.js')
    .addEntry('sidebar-element-js', './assets/js/sidebar-element.js')
    .addEntry('loading-component-js', './assets/js/loading-component.js')
    .addEntry('database-table-browser-js', './assets/js/database-table-browser.js')

    // copy static assets
    .copyFiles(
        {
            from: './assets/images', 
            to: 'images/[path][name].[ext]' 
        }
    )

    // other webpack configs
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    // postcss configs (tailwindcss)
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: {
                tailwindcss: {
                    content: [
                        "./assets/**/*.js",
                        "./view/**/*.twig",
                    ],
                    theme: {
                        extend: {
                            screens: {
                                'phn': '340px',
                            },
                        },
                    },
                    plugins: [],
                    safelist: [
                        'text-white',
                        'text-red-400',
                        'text-blue-400',
                        'text-green-400',
                        'text-yellow-400'
                    ],
                },
                autoprefixer: {},
            }
        };
    })

    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
;

module.exports = Encore.getWebpackConfig();
