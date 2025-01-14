const mix = require('laravel-mix');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
const {
  CleanWebpackPlugin
} = require('clean-webpack-plugin');

mix.js('resources/src/main.js', 'public')
  .js('resources/src/login.js', 'public')
  .js('resources/src/IndexedDBHelper.js','public')
  .vue();

// Replace the existing mix.webpackConfig block with this updated block
mix.webpackConfig({
  output: {
    filename: 'js/[name].min.js',
    chunkFilename: 'js/bundle/[name].[hash].js',
  },
  plugins: [
    new MomentLocalesPlugin(),
    new CleanWebpackPlugin({
      cleanOnceBeforeBuildPatterns: ['./js/*'],
    }),
  ],
});
