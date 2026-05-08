import '@scss/layouts/mytrainingsessions.scss';
import React, { useState, useReducer, useEffect } from 'react';
import axios from 'axios';
import TrainingSessionTable from '../global/TrainingsessiontableDetail';
import ReactPaginate from 'react-paginate';

const sessionState = {
  loading: false,
  data: [],
  error: null,
};
const pageSize = 10;
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
const RequestedTrainingSessions = () => {
  const [sessions, sessionDispatch] = useReducer(sessionReducer, sessionState);

  const getSessions = (coach_id = null) => {
    sessionDispatch({ type: 'GET_SESSION_REQUEST' });
    axios
      .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
        action: 'dffh_ajax_list_requested_training_session',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          coach_id,
        },
      })
      .then((res) => {
        sessionDispatch({ type: 'GET_SESSION_SUCCESS', data: res.data.data });
      })
      .catch((err) => {
        console.log(err);
        sessionDispatch({ type: 'GET_SESSION_ERROR', error: err });
      });
  };
  useEffect(() => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('coach_id');
    getSessions(id);
  }, []);

  const [pageNumber, setPageNumber] = useState(0);
  const handleClickPage = (e) => {
    setPageNumber(e.selected);
  };

  const [data, setData] = useState([]);

  useEffect(() => {
    if (sessions.data.length > 0) {
      if (pageNumber > 0) setData(sessions.data.slice(pageNumber * pageSize, pageNumber * pageSize + pageSize));
      else setData(sessions.data.slice(0, pageSize));
    }
  }, [pageNumber, sessions.data]);
console.log(data);
  return (
    <div id="react-mytrainingsession">
      <h2 className="title-training">Requested coaching sessions</h2>
      <TrainingSessionTable data={data}></TrainingSessionTable>
      <div className="container d-flex items-center flex-col flex-column">
        <div className="d-flex flex-column items-center gap-2 mt-6 mb-32 row-fluid">
          <div className="d-flex items-center flex-column align-items-center span6">
            <div className="pagination-mycourses">
              <ReactPaginate
                breakLabel="..."
                pageRangeDisplayed={3}
                marginPagesDisplayed={2}
                nextLabel=">"
                onPageChange={handleClickPage}
                pageCount={Math.ceil(sessions.data.length / pageSize)}
                previousLabel="<"
                renderOnZeroPageCount={null}
                forcePage={pageNumber}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default RequestedTrainingSessions;
