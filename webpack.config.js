const webpack = require('webpack');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin;
const ProvidePlugin = webpack.ProvidePlugin;
const path = require('path');

const isProdBuild = process.argv.indexOf("-p") !== -1;

const vendor = __dirname + '/node_modules';
const distTarget = __dirname + '/public/js';

module.exports = {
    context: __dirname,
    entry: {
        app: './js/app.js',
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
    devtool: isProdBuild ? '' : 'eval-source-map',
    output: {
        path: distTarget,
        filename: '[name].js'
    },
    resolve: {
        alias: {
            'jquery':                   path.resolve(vendor, 'jquery/dist/jquery.js'),
            'jquery.tablesaw':          path.resolve(vendor, 'tablesaw/dist/stackonly/tablesaw.stackonly.jquery.js'),
            'jquery.tablesaw.init':     path.resolve(vendor, 'tablesaw/dist/tablesaw-init.js'),
            'jquery.typed':             path.resolve(vendor, 'typed.js/lib/typed.js')
        }
    },
    module: {
        loaders: [
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: "style-loader",
                    use: ["css-loader", "sass-loader"]
                })
            }, {
                test: /\.nunj?$/,
                loader: 'nunjucks-loader'
            }, {
                test: /\.(js|jsx)?$/,
                exclude: /(node_modules)/,
                use: [
                    'babel-loader',
                    {
                        loader: 'eslint-loader',
                        options: {
                            cache: true,
                            failOnError: true
                        }
                    }
                ]
            }, {
                test: require.resolve('jquery'), //tablesaw's new version looks for jquery on the window object
                use: [{
                    loader: 'expose-loader',
                    options: 'jQuery'
                }]
            }
        ]
    },
    plugins: [
        new CommonsChunkPlugin({ name: 'vendor', filename: 'vendor.bundle.js'}),
        new ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery"
        }),
        new ExtractTextPlugin("../css/style.css"),
    ]
};
