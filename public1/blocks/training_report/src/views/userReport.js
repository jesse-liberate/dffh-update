import React from 'react';
import ReactDOM from 'react-dom';
import User from './User/User';

console.log('userReport loaded')

const jsx = (
    <div id="react-user-report">
      <User />
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-user-report'));