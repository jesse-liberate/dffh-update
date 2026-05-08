import '@scss/layouts/course.scss'
import React from 'react';
import ReactDOM from 'react-dom';
import StarRatings from 'react-star-ratings';

console.log('module/glbal/courserate.js loaded')

let brandcolor = M.theme.brand.brandcolor2
// let courseRatePlugin =  M.theme.plugins.courserating
// ReactDOM.render(courseRate, document.getElementsByClassName('mount-react-course-courserate').forEach(el => ReactDOM.render(courseRate, el)));
// console.log(document.getElementsByClassName('mount-react-course-courserate'));
Array.from(document.getElementsByClassName('mount-react-course-courserate'))
    .forEach(el => {
        const rate = parseFloat(el.dataset.rate);
        const courseRate = <StarRatings
                            rating={rate}
                            starRatedColor={brandcolor}
                            numberOfStars={5}
                            starDimension="13px"
                            starSpacing="1px"
                            />;
        ReactDOM.render(courseRate, el);
    })