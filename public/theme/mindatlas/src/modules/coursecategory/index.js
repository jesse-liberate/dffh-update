import "@scss/layouts/coursecategory.scss";

import React from "react";
import ReactDOM from "react-dom";
import CourseBox from "../global/Coursebox";
import CourseRate from "../global/Courserate";
import LearningProgress from "./LearningProgress";

console.log("module/coursecategory/index.js loaded");

const coursebox = <CourseBox></CourseBox>;

// const coursebox = '';

// ReactDOM.render(coursebox, document.getElementById('mount-react-course-category-box'));

const $learningProgress = $("#react-mount-learning-progress");
if ($learningProgress.length) {
    ReactDOM.render(
        <LearningProgress></LearningProgress>,
        $learningProgress[0]
    );
}
