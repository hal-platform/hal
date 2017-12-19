let webpack = require('webpack'),
    CommonsChunkPlugin = webpack.optimize.CommonsChunkPlugin,
    ProvidePlugin = webpack.ProvidePlugin,

    ExtractTextPlugin = require('extract-text-webpack-plugin'),

    path = require('path');

let isProdBuild = process.argv.indexOf('-p') !== -1;

let vendorPath = path.resolve(__dirname, 'node_modules');

let sourcePathJS = path.resolve(__dirname, 'js'),
    targetPathJS = path.resolve(__dirname, 'public/js');

let sourcePathCSS = path.resolve(__dirname, 'sass'),
    targetPathCSS = path.resolve(__dirname, 'public/css');

let js_config = {
    context: __dirname,
    entry: {
        app: `${sourcePathJS}/app.js`,
        vendor: [
            'ansi_up',
            'durationjs',
            'favico.js',
            'fuse.js',

            'jquery',
            'jquery.typed',

            'sugar-date',
            'svg4everybody',
            'xss-filters'
        ]
    },
    output: { path: targetPathJS, filename: '[name].js' },

    devtool: isProdBuild ? '' : 'eval-source-map',

    resolve: {
        alias: {
            'jquery':       `${vendorPath}/jquery/dist/jquery.js`,
            'jquery.typed': `${vendorPath}/typed.js/lib/typed.js`
        }
    },

    module: {
        rules: [
            {
                test: /\.(js|jsx)?$/,
                exclude: /(node_modules)/,
                use: [
                    'babel-loader',
                    {
                        loader: 'eslint-loader',
                        options: {
                            cache: true,
                            failOnError: isProdBuild // fail on error only if building for prod
                        }
                    }
                ]
            }
        ]
    },

    plugins: [
        new CommonsChunkPlugin({ name: 'vendor', filename: 'vendor.bundle.js'}),
        new ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery'
        })
    ]
};

let css_config = {
    context: __dirname,
    entry: {
        style: `${sourcePathCSS}/style.scss`
    },
    output: { path: targetPathCSS, filename: '[name].js' },

    devtool: isProdBuild ? '' : 'eval-source-map',

    module: {
        rules: [
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract([
                    'css-loader',
                    {
                        loader: 'sass-loader',
                        options: { includePaths: [ vendorPath ] }
                    }
                ])
            }
        ]
    },

    plugins: [
        new ExtractTextPlugin(`[name].css`)
    ]
};

module.exports = [js_config, css_config];
