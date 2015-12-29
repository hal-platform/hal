var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var vendor = __dirname + '/../node_modules';
var target = __dirname + '/../public/js';

module.exports = {
    context: __dirname,
    entry: {
        app: './app.js',
        vendor: [
            'durationjs',
            'favico.js',
            'fuse.js',

            'jquery',
            'jquery.tablesaw',
            'jquery.tablesaw.init',
            'jquery.typed',

            'nunjucks',
            'sugar-date',
            'svg4everybody'
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
                test: /\.js?$/,
                exclude: /(node_modules|bower_components)/,
                loader: 'babel',
                query: {
                    cacheDirectory: true,
                    presets: ["es2015"]
                }
            }
        ]
    },
    output: {
        path: target,
        filename: '[name].js'
    },
    plugins: [
        new CommonsChunkPlugin(
            /* chunkName= */'vendor',
            /* filename= */'vendor.bundle.js'
        ),
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
