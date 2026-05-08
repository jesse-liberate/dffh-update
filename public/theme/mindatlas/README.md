This theme is meant to be an example theme for extending Boost.

It comes with maximum comments for all code and shows how to extend and add settings
to a Boost based theme.

It also looks nice!


Rename the theme

1. Rename the "rawtheme" folder to "newtheme" (use lowercases).
2. Rename  lang/en/theme_mindatlastheme.php  to  lang/en/theme_newtheme.php.
3. Replace all strings with the following:

theme_mindatlastheme	theme_newtheme
theme/mindatlastheme	theme/newtheme
Theme Mindatlastheme	Theme NewTheme
'mindatlastheme'	'newtheme'

Add scripts to package.json
`
  "scripts": {
    "theme-prod": "webpack --mode=production --config wp-content/themes/coolau/webpack.config.js",
    "theme-dev": "webpack --mode=development --watch --config wp-content/themes/coolau/webpack.config.js"
  },
`

Install npm packages
    1. `npm i -D babel-loader @babel/polyfill @babel/core @babel/preset-env @babel/preset-react @babel/plugin-proposal-class-properties webpack-cli webpack`
    2. `npm i -D mini-css-extract-plugin sass-loader node-sass postcss-loader style-loader css-loader`
    3. `npm i react react-dom axios react-app-polyfill react-transition-group react-star-ratings`
    4. `npm i nodemon express cors`



Notes:
1. html sematic
  1. heading, h1, h2, h3, label

2. clean up unused code, add description if need, clean every day or when the part is finished

3. clean up console log when the part is finished

4. pretter

5. conside mobile when design layout

