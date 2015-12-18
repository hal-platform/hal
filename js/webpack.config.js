var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var vendor = __dirname + '/../bower_components';

module.exports = {
    context: __dirname,
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

            'favico':               vendor + '/favico.js/favico.js',
            'jquery':               vendor + '/jquery/dist/jquery.js',
            'jquery.searchable':    vendor + '/jquery-searchable/jquery.searchable.js',
            'jquery.sortable':      vendor + '/html5sortable/jquery.sortable.js',
            'jquery.tablesaw':      vendor + '/tablesaw/dist/stackonly/tablesaw.stackonly.js',

            'nunjucks':             vendor + '/nunjucks/browser/nunjucks.js',
            'sugarjs-date':         vendor + '/sugarjs-date/sugar-date.js',
            'svg4everybody':        vendor + '/svg4everybody/svg4everybody.js',
            'typed':                vendor + '/typed.js/js/typed.js'
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
};
