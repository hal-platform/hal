var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var root = __dirname + '/js';
var vendorRoot = root + '/vendor';

module.exports = {
    context: root,
    entry: {
        app: './app.js',
        vendor: [
            'jquery',
            'jquery.searchable',
            'jquery.sortable',
            'jquery.tablesaw',

            'crossroads',
            'moment',
            'nunjucks',
            'signals',
            'svg4everybody',
            'typed',
            'underscore'
        ]
    },

    resolve: {
        alias: {
            'jquery':               vendorRoot + '/jquery-2.1.3.js',
            'jquery.searchable':    vendorRoot + '/jquery.searchable.js',
            'jquery.sortable':      vendorRoot + '/jquery.sortable.min.js',
            'jquery.tablesaw':      vendorRoot + '/jquery.tablesaw-1.0.4.js',

            'crossroads':           vendorRoot + '/crossroads-0.12.0.js',
            'moment':               vendorRoot + '/moment-2.9.0.js',
            'nunjucks':             vendorRoot + '/nunjucks-1.3.3.js',
            'signals':              vendorRoot + '/signals-1.0.0.js',
            'svg4everybody':        vendorRoot + '/svg4everybody-1.0.0.js',
            'typed':                vendorRoot + '/typed.js',
            'underscore':           vendorRoot + '/underscore-1.8.3.js',
        }
    },
    output: {
        filename: '[name].js'
    },
    plugins: [
        new CommonsChunkPlugin(
            /* chunkName= */'vendor',
            /* filename= */'vendor.bundle.js'
        ),
        new webpack.optimize.UglifyJsPlugin({
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
}
