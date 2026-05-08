import '@scss/layouts/mycoachingsessions.scss';
import React, { useReducer, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import TrainingSessionTable from '../global/listCoachFormSetup';

const sessionState = {
  loading: false,
  data: [],
  error: null,
};
const SessionStatus = {
  '-1': 'past events',
  10: 'coming event',
  20: 'cancelled',
  30: 'declined',
  40: 'requested',
  50: 'approved',
  60: 'waitlisted',
  70: 'booked',
  80: 'Not show',
  90: 'partially attended',
  100: 'fully attended',
};
const CoachFormSetup = () => {
  const [data, setData] = useState([]);

  const getSessions = () => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_ajax_list_forms',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
        setData(res.data.data);
        console.log('res', res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    getSessions();
  }, []);

  const [valueSelect, setValueSelect] = useState('all');
  const handleRemove = (id) => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_ajax_remove_formbuilder',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          formid: id,
        },
      })
      .then((res) => {
        getSessions();
        console.log('res', res.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  if (!data) return null;
  return (
    <div id="react-mycoachingsessions">
      <div className="simplesearchform d-flex flex-row justify-content-end">
        
        <div className="form-group">
          <a
            style={{ color: 'white' }}
            className="btn-request-session"
            href={M.cfg.wwwroot + '/theme/mindatlas/pages/create-form-builder.php'}>
            Add new form
          </a>
        </div>
      </div>
      <div className="d-flex flex-row justify-content-between mb-3 mt-1">
        <h2 className="title-training">Form builder</h2>
      </div>
      <TrainingSessionTable handleRemove={handleRemove} data={data}></TrainingSessionTable>
    </div>
  );
};

export default CoachFormSetup;
