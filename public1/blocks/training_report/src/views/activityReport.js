import React from 'react';
import ReactDOM from 'react-dom';
import Activity from './Activity/Activity';

console.log('activityReport loaded')

const jsx = (
    <div id="react-activity-report">
      <Activity />
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-activity-report'));