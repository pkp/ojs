var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var OptimizeCSSPlugin = require('optimize-css-assets-webpack-plugin');

function resolve (dir) {
  return path.join(__dirname, '..', dir)
}

var isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  entry: [
    'babel-polyfill',
    './lib/ui-library/src/styles/_global.less',
    './js/load.js'
  ],
  output: {
    path: path.resolve(__dirname),
    filename: 'js/build.js'
  },
  module: {
    rules: [
      {
        test: /\.(js|vue)$/,
        loader: 'eslint-loader',
        enforce: 'pre',
        options: {
          formatter: require('eslint-friendly-formatter')
        }
      },
      {
        test: /\.less$/,
        loader: ExtractTextPlugin.extract({
          use: [
            {
              loader: 'css-loader',
            },
            {
              loader: 'less-loader',
            }
          ],
          fallback: 'style-loader',
        })
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader',
        options: {
          loaders: {
            less: ExtractTextPlugin.extract({
              use: [
                {
                  loader: 'css-loader',
                },
                {
                  loader: 'less-loader',
                }
              ],
              fallback: 'vue-style-loader'
            })
          }
        }
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      },
    ]
  },
  resolve: {
    alias: {
      'vue$': 'vue/dist/vue.common.js',
      '@': path.resolve(__dirname, 'lib/ui-library/src')
    }
  },
  performance: {
    hints: false
  },
  devtool: isProduction ? false : '#eval-source-map',
  plugins: [
    // http://vuejs.github.io/vue-loader/en/workflow/production.html
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: isProduction ? '"production"' : '"development"'
      }
    }),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        warnings: isProduction ? false : true
      },
      sourceMap: isProduction ? false : true
    }),
    // extract css into its own file
    new ExtractTextPlugin({
      filename: 'styles/build.css'
    }),
    // Compress extracted CSS. We are using this plugin so that possible
    // duplicated CSS from different components can be deduped.
    new OptimizeCSSPlugin({
      cssProcessorOptions: {
        safe: true
      }
    }),
  ],
};
