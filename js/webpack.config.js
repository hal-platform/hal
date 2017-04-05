var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var vendor = __dirname + '/../node_modules';
var distTarget = __dirname + '/../public/js';

module.exports = {
    context: __dirname,
    entry: {
        app: './app.js',
        vendor: [
            'ansi_up',
            'durationjs',
            'favico.js',
            'fuse.js',

            'react',
            'react-dom',

            'jquery',
            'jquery.tablesaw',
            'jquery.tablesaw.init',
            'jquery.typed',

            'sugar-date',
            'svg4everybody',
            'xss-filters'
        ]
    },

    resolve: {
        alias: {
            'jquery':                  vendor + '/jquery/dist/jquery.js',
            'jquery.tablesaw':         vendor + '/tablesaw/dist/stackonly/tablesaw.stackonly.js',
            'jquery.tablesaw.init':    vendor + '/tablesaw/dist/tablesaw-init.js',
            'jquery.typed':            vendor + '/typed.js/js/typed.js',
        }
    },
    module: {
        loaders: [
            {
                test: /nunjucks\/browser\/nunjucks\.js$/,
                loader: 'exports?nunjucks'
            },
            {
                test: /\.nunj?$/,
                loader: 'nunjucks-loader'
            },
            {
                test: /\.(js|jsx)?$/,
                exclude: /(node_modules)/,
                loader: 'babel',
                query: {
                    cacheDirectory: true,
                    presets: ['es2015', 'react']
                }
            }
        ]
    },
    output: {
        path: distTarget,
        filename: '[name].js'
    },
    plugins: [
        new CommonsChunkPlugin('vendor', 'vendor.bundle.js'),
        new UglifyJsPlugin({
            compress: {
                warnings: false
            }
        }),
        new ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery"
        })
    ]
};
