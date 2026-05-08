import React from 'react';
import ReactDOM from 'react-dom';
import Individual from './Individual/Individual';


console.log('individualReport loaded')

const jsx = (
    <div id="react-individual-report">
      <Individual />
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-individual-report'));