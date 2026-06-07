import '@scss/theme.scss';
import '@scss/layouts/frontpage.scss';
import '@scss/layouts/mytrainingsessions.scss';
import Lib from './lib/index';
import './modules/login/index.js'; //include login js and css because forgotpassword page is under standard layout
import React from 'react';
import ReactDOM from 'react-dom';
import ListGroupNav from './modules/global/ListGroupNav';
import Frontpage from './modules/frontpage';
import NavLinks from './modules/global/NavLinks';
import MyCoachingSessionsPage from './modules/mycoachingsession';
import CoachFormSetup from './modules/coachFormSetup';
import DetailCoachFormSetup from './modules/coachFormSetup/detail';
import CoachCreateForm from './modules/coachFormSetup/create';
import CoachEditForm from './modules/coachFormSetup/edit';
import MyCoachingSessionsManagerPage from './modules/mycoachingsessionmanager';
import MyTrainingSessionsPage from './modules/mytrainingsession';
import BannerTrainingSession from './modules/mytrainingsession/banner';
import RequestSession from './modules/requestsession';
import RequestedTrainingSessions from './modules/requestedTrainingSessions';
import DetailRequestedTrainingSessions from './modules/requestedTrainingSessions/detail';
import 'react-app-polyfill/ie9';

console.log('theme.js loaded');

// navbar links
const $links = $('#react-mount-nav-link-items');
if ($links.length) {
  ReactDOM.render(<NavLinks></NavLinks>, $links[0]);
}

// modify nav-drawer site list-group
if ($('.list-group[aria-label="Site"]').length) {
  ReactDOM.render(<ListGroupNav></ListGroupNav>, $('.list-group[aria-label="Site"]')[0]);
}
//signup page

// frontpage
const $frontpage = $('#mount-react-frontpage');
if ($frontpage.length) {
  ReactDOM.render(<Frontpage></Frontpage>, $frontpage[0]);
}
//my tranining sessions page
const $trainingsessionpage = $('#mount-react-trainingsessionpage');
if ($trainingsessionpage.length) {
  console.log('loading training session page module');
  ReactDOM.render(<MyTrainingSessionsPage></MyTrainingSessionsPage>, $trainingsessionpage[0]);
}

//my banner training sessions page
const $mytrainingsessionbanner = $('#mount-react-mytrainingsessionbanner');
if ($mytrainingsessionbanner.length) {
  ReactDOM.render(
    <BannerTrainingSession title="My training sessions"></BannerTrainingSession>,
    $mytrainingsessionbanner[0]
  );
}

//my coaching sessions page
const $coachingsessionspage = $('#mount-react-coachingsessionspage');
if ($coachingsessionspage.length) {
  console.log('loading coaching session page module');

  ReactDOM.render(<MyCoachingSessionsPage></MyCoachingSessionsPage>, $coachingsessionspage[0]);
}

//my banner training sessions page
const $mycoachingsessionbanner = $('#mount-react-mycoachingsessionbanner');
if ($mycoachingsessionbanner.length) {
  ReactDOM.render(
    <BannerTrainingSession title="My coaching sessions"></BannerTrainingSession>,
    $mycoachingsessionbanner[0]
  );
}
//my banner form builder
const $formBuilder = $('#mount-react-form-builder');
if ($formBuilder.length) {
  ReactDOM.render(<BannerTrainingSession title="Form builder"></BannerTrainingSession>, $formBuilder[0]);
}
//request sesstion
const $requestsessionspage = $('#mount-react-requestsessionspage');
if ($requestsessionspage.length) {
  console.log('loading request session page module');

  ReactDOM.render(<RequestSession></RequestSession>, $requestsessionspage[0]);
}

//coaching session manager
const $coachingSessionManager = $('#mount-react-coachingSessionManager');
if ($coachingSessionManager.length) {
  console.log('load coaching session manager :>> ');
  ReactDOM.render(<MyCoachingSessionsManagerPage />, $coachingSessionManager[0]);
}

// coach form setup
const $coachFromSetup = $('#mount-react-coach-form-setup');
if ($coachFromSetup.length) {
  ReactDOM.render(<CoachFormSetup />, $coachFromSetup[0]);
}
// coach form setup edit
const $coachFromSetupEdit = $('#mount-react-coach-form-setup-edit');
if ($coachFromSetupEdit.length) {
  ReactDOM.render(<CoachEditForm />, $coachFromSetupEdit[0]);
}
// detail form
const $detailCoachFromSetup = $('#mount-react-coach-form-setup-detail');
if ($detailCoachFromSetup.length) {
  ReactDOM.render(<DetailCoachFormSetup />, $detailCoachFromSetup[0]);
}

// create form
const $createFormCoach = $('#mount-react-coach-create-form');
if ($createFormCoach.length) {
  ReactDOM.render(<CoachCreateForm />, $createFormCoach[0]);
}

// requested training sessions
const $requestedTrainingSessions = $('#mount-react-requested-training-sessions');
if ($requestedTrainingSessions.length) {
  console.log('loading training session page module');
  ReactDOM.render(<RequestedTrainingSessions />, $requestedTrainingSessions[0]);
}

// detail requested training sessions
const $detailRequestedTrainingSession = $('#mount-react-detail-requested-training-sessions');
if ($detailRequestedTrainingSession.length) {
  console.log('loading detail requested training session page module');
  ReactDOM.render(<DetailRequestedTrainingSessions />, $detailRequestedTrainingSession[0]);
}

// issue: footer floating. reson: maybe caused by flex direction column is not supported
if (Lib.isIE()) {
  $('#page-wrapper').addClass('d-block');
}

function showCollectionStatementModal() {
  $('#collection_statement').modal('show');
}
document
  .querySelector('#page-footer #collection_statement_nav')
  .addEventListener('click', showCollectionStatementModal);
