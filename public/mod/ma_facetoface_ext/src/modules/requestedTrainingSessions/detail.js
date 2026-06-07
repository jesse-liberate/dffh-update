import '@scss/layouts/datePicker.scss';
import '@scss/layouts/mytrainingsessions.scss';
import axios from 'axios';
import React, { useEffect, useState, useRef} from 'react';
import DatePicker from 'react-datepicker';
import { EditorState } from 'draft-js';
import DOMPurify from 'dompurify';
import { Editor } from 'react-draft-wysiwyg';
import {stateToHTML} from 'draft-js-export-html';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';
import './style.scss';
import Select from 'react-select'
import makeAnimated from 'react-select/animated';
const animatedComponents = makeAnimated();
export const optionDate = [
  { id: 17, value: '8:00 AM', label: '8:00 AM' },
  { id: 18, value: '8:30 AM', label: '8:30 AM' },
  { id: 19, value: '9:00 AM', label: '9:00 AM' },
  { id: 20, value: '9:30 AM', label: '9:30 AM' },
  { id: 21, value: '10:00 AM', label: '10:00 AM' },
  { id: 22, value: '10:30 AM' ,label: '10:30 AM'},
  { id: 23, value: '11:00 AM',label: '11:00 AM' },
  { id: 24, value: '11:30 AM',label: '11:30 AM'},
  { id: 25, value: '12:00 PM',label: '12:00 PM' },
  { id: 26, value: '12:30 PM', label: '12:30 PM' },
  { id: 27, value: '1:00 PM' , label:'1:00 PM'},
  { id: 28, value: '1:30 PM' , label:'1:30 PM' },
  { id: 29, value: '2:00 PM' , label: '2:00 PM' },
  { id: 30, value: '2:30 PM', label: '2:30 PM'},
  { id: 31, value: '3:00 PM', label:  '3:00 PM'},
  { id: 32, value: '3:30 PM', label: '3:30 PM'},
  { id: 33, value: '4:00 PM', label: '4:00 PM'},
  { id: 34, value: '4:30 PM', label: '4:30 PM'},
  { id: 35, value: '5:00 PM', label: '5:00 PM'},
  { id: 36, value: '5:30 PM' , label:'5:30 PM' },
  { id: 37, value: '6:00 PM', label: '6:00 PM'},
  { id: 38, value: '6:30 PM', label: '6:30 PM'},
  { id: 39, value: '7:00 PM', label:  '7:00 PM'},
  { id: 40, value: '7:30 PM', label: '7:30 PM'},
  { id: 41, value: '8:00 PM', label: '8:00 PM'},
  { id: 42, value: '8:30 PM', label: '8:30 PM'},
  { id: 43, value: '9:00 PM', label: '9:00 PM'},
];

const sessionState = {
  loading: false,
  data: [],
  error: null,
};


const DetailRequestedTrainingSessions = () => {

  const getUserRole = () => {
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_check_coach',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
      
        setisCoach(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };

  const [data, setData] = useState(null);
  const [selectCoaching, setSelectCoaching] = useState(null);
  const [sessions, setSessions] = useState([]);
  const [users, setUsers] = useState([]);
  const [courseId, setCourseId] = useState(null);
  const [selectCoachingCoach, setSelectCoachingCoach] = useState(1);
  const [selectCourse, setSelectCourse] = useState('');
  const [duration, setDuration] = useState('');
  const [location, setLocation] = useState('');
  var [selectTime, setSelectTime] = useState('');
  const [startDate, setStartDate] = useState(new Date());
  const [staff, setStaff] = useState(null);
  const editorRef = useRef();
  const [editorState, setEditorState] = useState(
    () => EditorState.createEmpty(),
  );
  const [convertedContent, setConvertedContent] = useState(null);
  

  const getDetailRequest = (id) => {
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_detail_requested_training_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          request_id: id,
        },
      })
      .then((res) => {
        setData(res.data.data[0]);
        setSelectCourse(res.data.data[0].courseid);
        setSelectCoachingCoach(res.data.data[0].coachid);
        setStartDate(new Date(res.data.data[0].date));
        setSelectTime(res.data.data[0].time);
      })
      .catch((err) => {
        console.log(err);
      });
      axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_get_available_users',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          request_id: id,
        },
      })
      .then((res) => {
        setUsers(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  const getSession = (id, courseId) => {
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_get_session_course',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          request_id: id,
          course_id: courseId,
        },
      })
      .then((res) => {
        setSessions(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('request');
    const courseId = queryParams.get('courseId');
    setCourseId(courseId);
    getDetailRequest(id);
    getSession(id, courseId);
    getUserRole();
  }, []);
  const [param, setParam] = useState(null);
  const [isCoach, setisCoach] = useState(false);
  useEffect(() => {
    const data = sessions.find((item) => item.session_id == selectCoaching);
    setParam(data);
  }, [selectCoaching]);

  const handleRemoveRequest = () => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('request');
    const courseId = queryParams.get('courseId');
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_remove_requested_training_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          request_id: id,
          course_id: courseId,
        },
      })
      .then((res) => {
        window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/requested-training-sessions.php';
      })
      .catch((err) => {
        console.log(err);
      });
  };
  function handleChange(event) {
    const re = /^[0-9\b]+$/;
    if (event.target.value === '' || re.test(event.target.value)) {
    setDuration(event.target.value);
    }
  }
  function handlelocationChange(event) {
    setLocation(event.target.value);
  }
  function handleStaffChange(event){
    setStaff(event);
  }
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
  useEffect(() => {
    let prehtml = stateToHTML(editorState.getCurrentContent());
    let html = DOMPurify.sanitize(prehtml)
    setConvertedContent(html);
  }, [editorState]);

  const handleUpdateRequest = () => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('request');
    const courseId = queryParams.get('courseId');
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_update_requested_training_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          id: id,
          coachid: selectCoachingCoach,
          courseid: selectCourse,
          startdate: startDate,
          startTime: selectTime,
        },
      })
      .then((res) => {
        window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/my_coaching_sessions.php';
      })
      .catch((err) => {
        console.log(err);
      });
  };
 

  const createSession = () => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('request');
    const courseid = queryParams.get('courseId');
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_create_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          course_id: courseid,
          id: id,
          coachid: selectCoachingCoach,
          email: convertedContent,
          staff: staff,
          duration: duration,
          location: location,
          startdate: startDate,
          startTime: selectTime,
        },
      })
      .then((res) => {
        window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/requested-training-sessions.php?coach_id='+selectCoachingCoach
      })
      .catch((err) => {
        console.log(err);
      });
  };
 selectTime = { value: selectTime, label: `${selectTime}` }

  if (!data) return null;
  console.log(isCoach);
  
    return (
      <div id="react-mytrainingsession">
        <h2 className="title-training border">{`${data.firstname}  ${data.lastname} coaching session ${data.date}`}</h2>
        <div className="container d-flex items-center flex-col flex-column">
          <div className="d-flex flex-column items-center gap-2 mt-6 mb-32 row-fluid">
            <div className="form-group d-none row">
              <label htmlFor="inputPassword" className="col-sm-2 col-form-label">
                Course name
              </label>
              <div className="col-sm-3">
                <select
                  className="form-control selectOption requestSession"
                  id="sel1"
                  placeholder="Search by status"
                  value={selectCourse}
                  onChange={(e) => setSelectCourse(e.target.value)}>
                  <option value="">Select course</option>
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
            <div className="form-group row d-none">
              <label htmlFor="inputPassword" className="col-sm-2 col-form-label">
                Coach
              </label>
              <div className="col-sm-3">
                <select
                  className="form-control selectOption requestSession"
                  id="sel1"
                  placeholder="Search by status"
                  value={selectCoachingCoach}
                  onChange={(e) => setSelectCoachingCoach(e.target.value)}>
                  <option value="">Select coaching</option>
                  {listCoaching.map((coaching) => {
                    return (
                      <option key={coaching.id} value={coaching.id}>
                        {`${coaching.firstname} ${coaching.lastname}`}
                      </option>
                    );
                  })}
                </select>
              </div>
            </div>
  
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Date time
              </label>
              <div className="col-sm-3">
                <DatePicker
                  dateFormat="dd-MM-yyyy"
                  selected={startDate}
                  minDate={new Date()}
                  onChange={(date) => setStartDate(date)}
                  style={{ width: '100%' }}
                  className="form-control-ok form-control requestSession"
                />
              </div>
            </div>
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Time
              </label>
              <div className="col-sm-3">
                {selectTime != null && <Select
                  id="selecttime"
                  placeholder="Select Time"
                  value={selectTime}
                  onChange={(e) => setSelectTime(e.value)}
                  options={optionDate}/>}
                
              </div>
            </div>
            <>
            {isCoach == false && (
               <div className="d-none">

<div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Duration
              </label>
              <div className="col-sm-3">
              <input type="text" value={duration} onChange={handleChange} />
              </div>
            </div>
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Location
              </label>
              <div className="col-sm-3">
              <input type="text" value={location} onChange={handlelocationChange} />
              </div>
            </div>
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Select staff that will be booked
              </label>
              <div className="col-sm-3">
              <Select 
                components={animatedComponents}
                isMulti
                styles={{ menuPortal: base => ({ ...base, zIndex: 9999 }) }}
                options={users}
                onChange={handleStaffChange} />
              </div>
              
            </div>
            <div className="form-group row email-row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
               Email text
              </label>
              <div className="col-sm-9">
              <Editor
        editorState={editorState}
        onEditorStateChange={setEditorState}
        ref={editorRef}
        wrapperClassName="wrapper-class"
  editorClassName="editor-class"
  toolbarClassName="toolbar-class"
      />
              </div>
              </div>

               </div>)}
               {isCoach && (
               <div className="test">

<div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Duration
              </label>
              <div className="col-sm-3">
              <input type="text" value={duration} onChange={handleChange} />
              </div>
            </div>
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Location
              </label>
              <div className="col-sm-3">
              <input type="text" value={location} onChange={handlelocationChange} />
              </div>
            </div>
            <div className="form-group row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
                Select staff that will be booked
              </label>
              <div className="col-sm-3">
              <Select 
                components={animatedComponents}
                isMulti
                styles={{ menuPortal: base => ({ ...base, zIndex: 9999 }) }}
                options={users}
                onChange={handleStaffChange} />
              </div>
              
            </div>
            <div className="form-group row email-row">
              <label htmlFor="date-time" className="col-sm-2 col-form-label">
               Email text
              </label>
              <div className="col-sm-9">
              <Editor
        editorState={editorState}
        onEditorStateChange={setEditorState}
        ref={editorRef}
        wrapperClassName="wrapper-class"
  editorClassName="editor-class"
  toolbarClassName="toolbar-class"
      />
              </div>
              </div>
              </div> )}
            </>

          
             
            {/* {data.isAdmin && (
              <div className="form-group row">
                <label htmlFor="session" className="col-sm-2 col-form-label">
                  Session
                </label>
                <div className="col-sm-3">
                  <select
                    className="form-control selectOption requestSession"
                    id="sel1"
                    placeholder="Search by status"
                    value={selectCoaching}
                    disabled={!data.isAdmin}
                    onChange={(e) => setSelectCoaching(e.target.value)}>
                    <option value="">Select session</option>
                    {sessions.map((session) => {
                      return (
                        <option key={session.facetoface_id} value={session.session_id}>
                          {`${session.facetoface_name} -  ${session.data_field}`}
                        </option>
                      );
                    })}
                  </select>
                  <a href={M.cfg.wwwroot + '/course/view.php?id=' + courseId}>Create session</a>
                </div>
              </div>
            )} */}
  
            <div className="d-flex flex-row justify-content-center align-items-center margin-200">
             
                <>
                  <a
                    className="btn-request-session mr-3"
                    disabled={!staff}
                    style={{
                      backgroundColor: !staff ? 'gray' : '#29625f',
                    }}
                    onClick={createSession}>
                    Approve
                  </a>
                </>
                {isCoach == false  &&  (
              
               <a className="btn-request-session mr-3" onClick={handleUpdateRequest}>
                Update
              </a> )}
              <a className="btn-request-session mr-3 bg-alert" onClick={handleRemoveRequest} >
                Remove
              </a>
            </div>
          </div>
        </div>
      </div>
    );
  
                  }
  

export default DetailRequestedTrainingSessions;
