import React from 'react';
import ReactDOM from 'react-dom';
import Coaching from './Coaching/Coaching';

console.log('coachingReport loaded')

const jsx = (
    <div id="react-coaching-report">
      <Coaching />
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-coaching-report'));