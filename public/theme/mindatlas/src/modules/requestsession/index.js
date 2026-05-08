import '@scss/layouts/mycoachingsessions.scss';
import React, { useReducer, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import DatePicker from 'react-datepicker';
import '@scss/layouts/datePicker.scss';
import moment from 'moment';
// import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

// import useHistory from 'react-router-dom';

export const optionDate = [
  { id: 17, value: '8:00 AM' },
  { id: 18, value: '8:30 AM' },
  { id: 19, value: '9:00 AM' },
  { id: 20, value: '9:30 AM' },
  { id: 21, value: '10:00 AM' },
  { id: 22, value: '10:30 AM' },
  { id: 23, value: '11:00 AM' },
  { id: 24, value: '11:30 AM' },
  { id: 25, value: '12:00 PM' },
  { id: 26, value: '12:30 PM' },
  { id: 27, value: '1:00 PM' },
  { id: 28, value: '1:30 PM' },
  { id: 29, value: '2:00 PM' },
  { id: 30, value: '2:30 PM' },
  { id: 31, value: '3:00 PM' },
  { id: 32, value: '3:30 PM' },
  { id: 33, value: '4:00 PM' },
  { id: 34, value: '4:30 PM' },
  { id: 35, value: '5:00 PM' },
  { id: 36, value: '5:30 PM' },
  { id: 37, value: '6:00 PM' },
  { id: 38, value: '6:30 PM' },
  { id: 39, value: '7:00 PM' },
  { id: 40, value: '7:30 PM' },
  { id: 41, value: '8:00 PM' },
  { id: 42, value: '8:30 PM' },
  { id: 43, value: '9:00 PM' },
];

const sessionState = {
  loading: false,
  data: [],
  error: null,
};

const sessionReducer = (state, action) => {
  switch (action.type) {
    case 'GET_SESSION_REQUEST':
      return {
        ...state,
        loading: true,
      };
    case 'GET_SESSION_SUCCESS':
      return {
        ...state,
        loading: false,
        data: action.data,
      };
    case 'GET_SESSION_ERROR':
      return {
        ...state,
        data: [],
        error: action.data,
      };
    default:
  }
};
const RequestSession = () => {
  // const history = useHistory();
  // const [sessions, sessionDispatch] = useReducer(sessionReducer, sessionState);
  const [courses, setCourses] = useState([]);
  const [listCoaching, setListCoaching] = useState([]);

  const getCoaching = () => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_get_coach_users',
      })
      .then((res) => {
        setListCoaching(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  const getListCourse = () => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_request_list_course',
      })
      .then((res) => {
        setCourses(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    getListCourse();
    getCoaching();
  }, []);

  const searchInput = useRef(null);
  const [selectCoaching, setSelectCoaching] = useState('');
  const [selectCourse, setSelectCourse] = useState('');
  const [selectTime, setSelectTime] = useState('');
  const [startDate, setStartDate] = useState(new Date());
  const getFullName = (id) => {
    const result = listCoaching.find((item) => item.id === id);
    const img = '<img style="border-radius: 50%; margin-right: 10px" className="round" src="' + result.img + '"></img>';
    return  ( <div dangerouslySetInnerHTML={{ __html: img + result.firstname + ' ' + result.lastname}} />);
  };

  const handleSendRequestSession = () => {
   
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_request_coaching_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          coachid: selectCoaching,
          courseid: courses[0].id,
          startdate: startDate,
          startTime: selectTime,
        },
      })
      .then((res) => {
        window.location.href = res.data.data.link 
      })
      .catch((err) => {
        console.log(err);
      });
  };
if(courses){
  var courseid = courses[0]
}
if(courseid){
 var id = courseid.id;
} 
if(courseid){
  return (
    <div id="react-requestsessions">
      <h2 className="title-training">Request coaching session</h2>
      <div className="d-none choose-coach">
        <h3 className="title-training border">1. Choose Course</h3>
        <div className="form-group row">
          <label htmlFor="inputPassword" className="col-sm-2 col-form-label">
            Course name
          </label>
          <div className="col-sm-3">
            <select
              className="form-control selectOption requestSession"
              id="sel1"
              placeholder="Search by status"
              defaultValue={id}
              onChange={(e) => setSelectCourse(e.target.value)}>
              {courses.map((coaching) => {
                return (
                  <option key={coaching.id} value={coaching.id}>
                    {`${coaching.fullname}`}
                  </option>
                );
              })}
            </select>
          </div>
        </div>
      </div>
        <div className="choose-coach d-none">
          <h3 className="title-training border">1. Choose Coach</h3>
          <div className="form-group row">
            <div className="col-sm-12">
            {listCoaching.map((coaching) => {
              var cardclasses = 'user-card';
              if(coaching.id == selectCoaching){
                cardclasses += ' selected';
              }
                  return (
                     <div key ={coaching.id} className={cardclasses}>
                      <button key ={coaching.id} onClick={(e) => console.log(coaching.id) + setSelectCoaching(coaching.id)} >
   <img className="round mr-2" src={coaching.img}></img>{ `${coaching.firstname} ${coaching.lastname}`}
   </button>  
                     </div>
                  );
                })}     
            </div>
          </div>
        </div>
      
        <div className="select-date">
          <h3 className="title-training border">1. Select a date and time</h3>
          <form className="row">
            <div className="form-group col-md-4">
              <div className="dateTimeBox">
                <label htmlFor="inputPassword" className="col-sm-2 col-form-label mr10">
                  <b>Date</b>
                </label>
                <DatePicker
                  dateFormat="dd/MM/yyyy"
                  selected={startDate}
                  minDate={startDate}
                  onChange={(date) => setStartDate(date)}
                />
              </div>
            </div>
            <div className="form-group col-md-4">
              <div className="dateTimeBox">
                <label htmlFor="inputPassword" className="col-sm-2 col-form-label mr10">
                  <b>Time</b>
                </label>
                <select
                  className="form-control selectOption requestSession"
                  id="sel1"
                  placeholder="Search by status"
                  value={selectTime}
                  onChange={(e) => setSelectTime(e.target.value)}>
                  <option value="">Select Time</option>
                  {optionDate.map((item) => {
                    return (
                      <option key={item.id} value={item.value}>
                        {`${item.value}`}
                      </option>
                    );
                  })}
                </select>
              </div>
            </div>
          </form>
        </div>
      
      {selectTime && startDate && (
        <div className="choose-coach">
          <h3 className="title-training border">2. Confirm your session time</h3>
          {/* <div className="coachingNameResult mb10">{getFullName(selectCoaching)}</div> */}
          <div>
            Date:<span className="coachingDateResult mb10 ml10">{moment(startDate).format('DD/MM/yyyy')}</span>
          </div>
          <div>
            Time:<span className="coachingTimeResult mb10 ml10">{selectTime}</span>
          </div>
        </div>
      )}
      <div className="d-flex flex-row justify-content-center align-items-center mt60">
        <button
          disabled={ !selectTime || !startDate}
          className="btn-request-session mr-3"
          style={{
            backgroundColor: !selectTime || !startDate ? '#d4d4d4' : '#29625f',
            fontWeight: 600,
          }}
          onClick={() => handleSendRequestSession()}>
          Send coaching request
        </button>
        <a className="cancelSession" href={M.cfg.wwwroot + '/theme/mindatlas/pages/my_coaching_sessions.php'}>
          Cancel
        </a>
      </div>
    </div>
  );
}else{
  return '';
}
  
};

export default RequestSession;
