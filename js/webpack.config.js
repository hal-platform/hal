var webpack = require('webpack');
var CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
var UglifyJsPlugin = webpack.optimize.UglifyJsPlugin;
var ProvidePlugin = webpack.ProvidePlugin;

var vendorOld = __dirname + '/../bower_components';
var vendor = __dirname + '/../node_modules';

module.exports = {
    context: __dirname,
    entry: {
        app: './app.js',
        vendor: [
            'favico',
            'fuse.js',
            'jquery',
            'jquery.tablesaw',
            'jquery.tablesaw.init',
            'jquery.hideseek',

            'nunjucks',
            'sugarjs-date',
            'svg4everybody',
            'typed'
        ]
    },

    resolve: {
        alias: {
            'favico':                  vendor + '/favico.js/favico.js',
            'fuse.js':                 vendor + '/fuse.js/src/fuse.js',
            'jquery':                  vendor + '/jquery/dist/jquery.js',
            'jquery.tablesaw':         vendor + '/tablesaw/dist/stackonly/tablesaw.stackonly.js',
            'jquery.tablesaw.init':    vendor + '/tablesaw/dist/tablesaw-init.js',
            'jquery.hideseek':         vendor + '/hideseek/jquery.hideseek.js',

            'nunjucks':                vendor + '/nunjucks/browser/nunjucks.js',
            'sugarjs-date':            vendor + '/sugar-date/sugar-date.js',
            'svg4everybody':           vendor + '/svg4everybody/dist/svg4everybody.js',
            'typed':                vendorOld + '/typed.js/js/typed.js'
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
