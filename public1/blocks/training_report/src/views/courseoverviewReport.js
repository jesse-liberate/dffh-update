import React from 'react';
import ReactDOM from 'react-dom';
import CourseOverview from './CourseOverview/CourseOverview';

// import resultPanel from '../components/resultPanel'

console.log('courseoverviewReport loaded')

const jsx = (
    <div id="react-courseoverview-report">
      <CourseOverview />
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-courseoverview-report'));