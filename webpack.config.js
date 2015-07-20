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
            'favico',
            'jquery',
            'jquery.searchable',
            'jquery.sortable',
            'jquery.tablesaw',

            'nunjucks',
            'sugarjs-date',
            'svg4everybody',
            'typed'
        ]
    },

    resolve: {
        alias: {

            'favico':               vendorRoot + '/favico.js/favico.js',
            'jquery':               vendorRoot + '/jquery/dist/jquery.js',
            'jquery.searchable':    vendorRoot + '/jquery-searchable/jquery.searchable.js',
            'jquery.sortable':      vendorRoot + '/html5sortable/jquery.sortable.js',
            'jquery.tablesaw':      vendorRoot + '/tablesaw/dist/stackonly/tablesaw.stackonly.js',

            'nunjucks':             vendorRoot + '/nunjucks/browser/nunjucks.js',
            'sugarjs-date':         vendorRoot + '/sugarjs-date/sugar-date.js',
            'svg4everybody':        vendorRoot + '/svg4everybody/svg4everybody.js',
            'typed':                vendorRoot + '/typed.js/js/typed.js'
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
