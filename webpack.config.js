const path = require('path');

module.exports = {
  entry: './assets/js/main.tsx',
  mode: 'development',
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      },
    ],
  },
  resolve: {
    extensions: ['.tsx', '.ts', '.js'],
    alias: {
      '@ui': path.resolve(__dirname, 'assets/js/ui'),
    }
  },
  output: {
    filename: 'main.js',
    path: path.resolve(__dirname, 'assets/js'),
  },
  devServer: {
    static: {
      directory: path.join(__dirname, '/'),
    },
    compress: true,
    port: 5000,
    host: '0.0.0.0',
  },
};
