import '@scss/layouts/mycoachingsessions.scss';
import React, { useReducer, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import TrainingSessionTable from '../global/CoachingTable';
import RequestTable from '../global/RequestsTable';
import ReactPaginate from 'react-paginate';

const pageSize = 10;
const sessionState = {
  loading: false,
  data: [],
  error: null,
};
const SessionStatus = {
  '-1': 'Past events',
  10: 'Coming event',
  20: 'Cancelled',
  30: 'Declined',
  40: 'Requested',
  50: 'Approved',
  60: 'Waitlisted',
  70: 'Booked',
  80: 'Not show',
  90: 'Partially attended',
  100: 'Fully attended',
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
const MyCoachingSessionsPage = () => {
  const [sessions, sessionDispatch] = useReducer(sessionReducer, sessionState);
  const [data, setData] = useState([]);

  const getSessions = () => {
    sessionDispatch({ type: 'GET_SESSION_REQUEST' });
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_list_coaching_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
        sessionDispatch({ type: 'GET_SESSION_SUCCESS', data: res.data.data });
        setData(res.data.data);
      })
      .catch((err) => {
        console.log(err);
        sessionDispatch({ type: 'GET_SESSION_ERROR', error: err });
      });
  };
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
  const getUserAdmin = () => {
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_check_admin',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
        setisAdmin(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };

  const userHasCoach = () => {
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_has_coach',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
        sethasCoach(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    getSessions();
    getUserRole();
    getUserAdmin();
    userHasCoach();
  }, []);

  const searchInput = useRef(null);
  const [valueSelect, setValueSelect] = useState('all');
  const [pageNumber, setPageNumber] = useState(0);
  const [isCoach, setisCoach] = useState(false);
  const [isAdmin, setisAdmin] = useState(false);
  const [hasCoach, sethasCoach] = useState(false);
  const handleClickPage = (e) => {
    setPageNumber(e.selected);
  };
  const handleSearch = (e) => {
    const search = searchInput.current.value;
    console.log('search', search);
    console.log('sessions :>> ', sessions);
    const dataSearch = sessions.data.filter((item) => {
      return item.name.toLowerCase().includes(search.toLowerCase());
    });
    console.log('dataSearch :>> ', dataSearch);
    setData(dataSearch);
  };

  useEffect(() => {
    if (sessions.data.length > 0) {
      if (pageNumber > 0) setData(sessions.data.slice(pageNumber * pageSize, pageNumber * pageSize + pageSize));
      else setData(sessions.data.slice(0, pageSize));
    }
  }, [pageNumber, sessions.data]);
  useEffect(() => {
    if (sessions.data.length > 0) {
      if (valueSelect !== 'all') {
        const dataSearch = sessions.data.filter((item) => {
          return item.statuscode === valueSelect;
        });
        setData(dataSearch);
      } else {
        setData(sessions.data);
      }
    }
  }, [valueSelect]);
  return (
    <div id="react-mycoachingsessions">
      {/* <div className="simplesearchform d-flex flex-row justify-content-between">
        <div className="mform form-inline simplesearchform">
          <div className="input-group">
            <input
              ref={searchInput}
              type="text"
              className="form-control search-input"
              placeholder=""
              aria-label="Search courses"
              name="q"
              data-region="input"
            />
            <div className="input-group-append">
              <button className="btn btn-primary search-icon bg-color-brand-1" onClick={() => handleSearch()}>
                <b>Search</b> */}
                {/* <i className="icon fa fa-search fa-fw " aria-hidden="true"></i>
                <span className="sr-only">Search courses</span> */}
              {/* </button>
            </div>
          </div>
        </div> */}

        {/* <div className="form-group">
          <select
            className="form-control selectOption"
            id="sel1"
            placeholder="Search by status"
            value={valueSelect}
            onChange={(e) => setValueSelect(e.target.value)}>
            <option value="all">Search by status</option>
            {Object.keys(SessionStatus).map((key) => {
              return (
                <option key={key} value={key}>
                  {SessionStatus[key]}
                </option>
              );
            })}
          </select>
        </div>
      </div> */}
      <div className="d-flex flex-row justify-content-between mb-6 mt-1">
        <h2 className="title-training">My coaching sessions</h2>
        {hasCoach == true && (
          <a
          style={{ color: 'white' }}
          className="btn-request-session"
          href={M.cfg.wwwroot + '/theme/mindatlas/pages/request_sessions.php'}>
          Request session
          </a>
        )}

{hasCoach == false && (
          <p
          style={{ color: 'black' }}
          >
          Your agency doesn't have a coach setup
          </p>
        )}
       {isAdmin== true && (
          <a
          style={{ color: 'white' }}
          className="btn-request-session"
          href={M.cfg.wwwroot + '/theme/mindatlas/pages/my_coaching_sessions_manager.php'}>
          View requests
          </a>
       )}
          {isCoach == true && isAdmin == false && (
               <a
               style={{ color: 'white' }}
               className="btn-request-session"
               href={M.cfg.wwwroot + '/theme/mindatlas/pages/requested-training-sessions.php?coach_id=' + M.user.id }>
               View requests
             </a>
            )} 
       
      </div>
      <TrainingSessionTable data={data}></TrainingSessionTable>
      <div className="d-flex items-center flex-col flex-column mt-5">
        <h2 className="title-training">My requests</h2>
        <RequestTable data={data}></RequestTable>
      </div>
    </div>
  );
};

export default MyCoachingSessionsPage;
console.log('module/mycoachingsessions/index.js loaded');
