/* admin-suite frontend webpack builder */
const Encore = require('@symfony/webpack-encore');

Encore
    // set build path
    .setOutputPath('public/assets/')
    .setPublicPath('/assets')

    // register css
    .addEntry('index-css', './assets/css/index.scss')

    // register js
    .addEntry('terminal-js', './assets/js/terminal.js')
    .addEntry('todo-manager-js', './assets/js/todo-manager.js')
    .addEntry('user-manager-js', './assets/js/user-manager.js')
    .addEntry('metrics-charts-js', './assets/js/metrics-charts.js')
    .addEntry('sidebar-element-js', './assets/js/sidebar-element.js')
    .addEntry('loading-component-js', './assets/js/loading-component.js')
    .addEntry('database-table-browser-js', './assets/js/database-table-browser.js')
    .addEntry('notifications-settings-js', './assets/js/notifications-settings.js')
    .addEntry('notification-subscriberr-js', './assets/js/notification-subscriber.js')

    // copy static assets
    .copyFiles(
        {
            from: './assets/images', 
            to: 'images/[path][name].[ext]' 
        }
    )

    // other webpack configs
    .splitEntryChunks()
    .enableSassLoader()
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

                                // screen sizes limit properties for show small screen error message
                                'xs': { max: "265px" },
                                'short': { raw: "(max-height: 160px)" }
                            },

                            // animations config
                            keyframes: {
                                popIn: {
                                    "0%": { opacity: "0", transform: "scale(0.5)" },
                                    "100%": { opacity: "1", transform: "scale(1)" },
                                },
                            },
                            animation: {
                                popin: "popIn 0.1s ease-out",
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
