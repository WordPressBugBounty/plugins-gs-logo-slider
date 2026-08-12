const path = require('path');

module.exports = {
  target: 'web',
  entry: {
    bundle: './src/index.jsx',
  },
  // Divi 5 exposes packages on window.vendor / window.divi — match official example externals.
  externals: {
    jquery: 'jQuery',
    lodash: 'lodash',
    react: ['vendor', 'React'],
    'react-dom': ['vendor', 'ReactDOM'],
    '@wordpress/hooks': ['vendor', 'wp', 'hooks'],
    '@wordpress/i18n': ['vendor', 'wp', 'i18n'],
    '@divi/rest': ['divi', 'rest'],
    '@divi/module': ['divi', 'module'],
    '@divi/module-library': ['divi', 'moduleLibrary'],
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'thread-loader',
            options: {
              workers: -1,
            },
          },
          {
            loader: 'babel-loader',
            options: {
              compact: false,
              presets: [
                [
                  '@babel/preset-env',
                  {
                    modules: false,
                    targets: '> 5%',
                  },
                ],
                '@babel/preset-react',
              ],
              cacheDirectory: false,
            },
          },
        ],
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.jsx', '.json'],
  },
  output: {
    filename: 'gs-logo-divi.js',
    path: path.resolve(__dirname, 'build'),
  },
};
