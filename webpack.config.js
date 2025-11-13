/* admin-suite frontend webpack builder */
const Encore = require('@symfony/webpack-encore');
 
Encore
    // set build path
    .setOutputPath('public/assets/')
    .setPublicPath('/assets')

    // register css
    .addEntry('index-css', './assets/css/index.scss')

    // register js
    .addEntry('sidebar-element-js', './assets/js/sidebar-element.js')
    .addEntry('loading-component-js', './assets/js/loading-component.js')
    .addEntry('profile-photo-view-toggle-js', './assets/js/profile-photo-viewer.js')
    .addEntry('metrics-charts-js', './assets/js/component/metrics/metrics-charts.js')
    .addEntry('user-manager-js', './assets/js/component/user-manager/user-manager.js')
    .addEntry('todo-manager-js', './assets/js/component/todo-manager/todo-manager.js')
    .addEntry('file-system-edit-js', './assets/js/component/file-system/file-system-edit.js')
    .addEntry('file-system-move-js', './assets/js/component/file-system/file-system-move.js')
    .addEntry('terminal-component-js', './assets/js/component/terminal/terminal-component.js')
    .addEntry('file-system-create-js', './assets/js/component/file-system/file-system-create.js')
    .addEntry('file-system-rename-js', './assets/js/component/file-system/file-system-rename.js')
    .addEntry('file-system-upload-js', './assets/js/component/file-system/file-system-upload.js')
    .addEntry('suite-config-manager-js', './assets/js/component/config-manager/suite-config-manager.js')
    .addEntry('log-manager-raw-viewer-js', './assets/js/component/log-manager/log-manager-raw-viewer.js')
    .addEntry('notifications-settings-js', './assets/js/component/notification/notifications-settings.js')
    .addEntry('system-resources-updater-js', './assets/js/component/dashboard/system-resources-updater.js')
    .addEntry('file-system-file-delete-js', './assets/js/component/file-system/file-system-file-delete.js')
    .addEntry('file-system-create-menu-js', './assets/js/component/file-system/file-system-create-menu.js')
    .addEntry('file-system-media-viewer-js', './assets/js/component/file-system/file-system-media-viewer.js')
    .addEntry('notification-subscriberr-js', './assets/js/component/notification/notification-subscriber.js')
    .addEntry('metrics-delete-confirmaton-js', './assets/js/component/metrics/metrics-delete-confirmaton.js')
    .addEntry('account-settings-table-js', './assets/js/component/account-settings/account-settings-table.js')
    .addEntry('database-table-browser-js', './assets/js/component/database-browser/database-table-browser.js')
    .addEntry('system-journalctl-log-card-js', './assets/js/component/system-audit/system-journalctl-log-card.js')
    .addEntry('file-system-mobile-dropdown-js', './assets/js/component/file-system/file-system-mobile-dropdown.js')
    .addEntry('metrics-aggregate-confirmation-js', './assets/js/component/metrics/metrics-aggregate-confirmation.js')
    .addEntry('file-system-create-directory-js', './assets/js/component/file-system/file-system-create-directory.js')
    .addEntry('file-system-syntax-highlight-js', './assets/js/component/file-system/file-system-syntax-highlight.js')
    .addEntry('exception-log-delete-confirmation-js', './assets/js/component/file-system/exception-log-delete-confirmation.js')

    // copy static assets
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]'
    })

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
                        "./view/**/*.twig"
                    ],
                    theme: {
                        extend: {
                            screens: {
                                'phn': '340px',
                                'xs': { max: "220px" },
                                'short': { raw: "(max-height: 160px)" }
                            },

                            // animations config
                            keyframes: {
                                popIn: {
                                    "0%": { opacity: "0", transform: "scale(0.5)" },
                                    "100%": { opacity: "1", transform: "scale(1)" }
                                }
                            },
                            animation: {
                                popin: "popIn 0.1s ease-out"
                            }
                        }
                    },
                    plugins: [],
                    safelist: [
                        'text-white',
                        'text-red-400',
                        'text-blue-400',
                        'text-green-400',
                        'text-purple-400',
                        'text-yellow-400'
                    ]
                },
                autoprefixer: {}
            }
        };
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
;

module.exports = Encore.getWebpackConfig();
