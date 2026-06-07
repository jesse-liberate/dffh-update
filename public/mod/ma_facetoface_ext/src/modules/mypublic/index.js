import '@scss/layouts/mypublic.scss'
import React from 'react';
import ReactDOM from 'react-dom';
import Profile from './Profile'

console.log('module/mypublic/index.js loaded')

let target = JSON.parse(document.getElementById('mount-react-mypublic').dataset.targetuser)
ReactDOM.render(<Profile target={target}></Profile>, document.getElementById('mount-react-mypublic'));