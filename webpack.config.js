var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var root = __dirname + '/js';
var vendorRoot = __dirname + '/bower_components';

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

            'jquery':               vendorRoot + '/jquery/dist/jquery.js',
            'jquery.searchable':    vendorRoot + '/jquery-searchable/jquery.searchable.js',
            'jquery.sortable':      vendorRoot + '/html5sortable/jquery.sortable.js',
            'jquery.tablesaw':      vendorRoot + '/tablesaw/dist/stackonly/tablesaw.stackonly.js',

            'crossroads':           vendorRoot + '/crossroads/dist/crossroads.js',
            'moment':               vendorRoot + '/moment/moment.js',
            'nunjucks':             vendorRoot + '/nunjucks/browser/nunjucks.js',
            'signals':              vendorRoot + '/js-signals/dist/signals.js',
            'svg4everybody':        vendorRoot + '/svg4everybody/svg4everybody.js',
            'typed':                vendorRoot + '/typed.js/js/typed.js',
            'underscore':           vendorRoot + '/underscore/underscore.js'
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
