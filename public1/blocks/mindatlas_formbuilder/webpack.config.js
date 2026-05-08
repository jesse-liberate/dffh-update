const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

// const { config } = require('process');

let config = {
  entry: {
    "index.bundle": path.join(__dirname, "src/index.js"),
    // "generalReports.bundle": path.join(__dirname, "src/views/generalReport.js"),
    // "activityReports.bundle": path.join(__dirname, "src/views/activityReport.js"),
    // "individualReports.bundle": path.join(__dirname, "src/views/individualReport.js"),
    // "courseoverviewReports.bundle": path.join(__dirname, "src/views/courseoverviewReport.js"),
    // "userReports.bundle": path.join(__dirname, "src/views/userReport.js"),
  },
  target: ['web', 'es5'],
  output: {
    filename: '[name].js',
    path: path.join(__dirname, '/dist'),
    publicPath: '../../dist/',
  },
  resolve: {
    alias: {
        'reactjs-dropdown-component': path.resolve(__dirname, './src/packages/Custom-ReactJS-Dropdown-Components/src/index.js'),
       //   '@lib': path.resolve(__dirname, './src/lib/'),
    },
    extensions: ['*', '.js', '.jsx']
  },
  module: {
    rules: [
      {
        test: /Worker[\/\\].*?\.js$/,
        loader: 'worker-loader',
      },
      {
        test: /\.(js|jsx)$/,
        loader: 'babel-loader',
        exclude: /node_modules/
      }, 
      {
        test: /\.s[ac]ss$/,
        use: [
          'style-loader',
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              esModule: false,
            }
          },
          {
              loader: "css-loader",
              options: { url: false }
          },
          'postcss-loader',
          'sass-loader'
        ],
      },
      {
        test: /\.css$/i,
        use: [
          "style-loader", 
          MiniCssExtractPlugin.loader,
          {
              loader: "css-loader",
              options: { url: false }
          },
      ],
    },
    {
      test: /\.svg$/,
      use: ['@svgr/webpack'],
    },
  ],
  },
  watchOptions: {
    aggregateTimeout: 600
  },
  plugins: [
    new MiniCssExtractPlugin({
    //   moduleFilename: ({ name }) => `${name.replace('javascript/', 'style/')}.css`,
    }),
  ]
}

module.exports = (env, argv) => {
  if (argv.mode === 'development') {
    config.mode = 'development'
    config.devtool = 'eval-cheap-source-map';
  }

  if (argv.mode === 'production') {
    config.mode = 'production'
  }

  return config
};
