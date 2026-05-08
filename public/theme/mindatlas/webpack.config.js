const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// const { config } = require('process');

let config = {
    entry: {
        'javascript/theme.bundle': path.join(__dirname, 'src/theme.js'),
        'javascript/login.bundle': path.join(
            __dirname,
            'src/modules/login/index.js'
        ),
        'javascript/signup.bundle': path.join(
            __dirname,
            'src/modules/signup/index.js'
        ),
        'javascript/course.bundle': path.join(
            __dirname,
            'src/modules/course/index.js'
        ),
        'javascript/coursecategory.bundle': path.join(
            __dirname,
            'src/modules/coursecategory/index.js'
        ),
        'javascript/mypublic.bundle': path.join(
            __dirname,
            'src/modules/mypublic/index.js'
        ),
    },
    target: ['web', 'es5'],
    output: {
        filename: '[name].js',
        path: path.join(__dirname, './'),
    },
    resolve: {
        alias: {
            '@scss': path.resolve(__dirname, './scss/'),
            '@modules': path.resolve(__dirname, './src/modules'),
            '@lib': path.resolve(__dirname, './src/lib/'),
            // 'react-star-rating-css': path.join(__dirname, '../node_modules/react-star-rating/dist/css/react-star-rating.min.css')
        },
        extensions: ['*', '.js'],
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/,
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: { url: false },
                    },
                    'sass-loader',
                ],
                
            },
            {
                test: /\.css$/,
                use: [
                  MiniCssExtractPlugin.loader,
                  'css-loader',
                ],
            }
        ],
    },
    watchOptions: {
        aggregateTimeout: 600,
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: ({ chunk }) =>
                `${chunk.name.replace('javascript/', 'style/')}.css`,
        }),
    ],
};

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.mode = 'development';
        config.devtool = 'eval-cheap-source-map';
    }

    if (argv.mode === 'production') {
        config.mode = 'production';
    }

    return config;
};
