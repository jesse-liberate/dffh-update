import '@scss/layouts/mycoachingsessions.scss';
import axios from 'axios';
import React, { useEffect, useState } from 'react';
import TableCoachManagement from './TableCoachManagement';

const MyCoachingSessionsManagerPage = () => {
  const [data, setData] = useState([]);

  const getSessions = () => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_coachmanagement/api/index.php', {
        action: 'dffh_ajax_list_coaching_session_manager',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
        },
      })
      .then((res) => {
        console.log('res', res.data.data);
        setData(res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    getSessions();
  }, []);
  return (
    <div id="react-mycoachingsessions">
      <div className="d-flex flex-row justify-content-between mb-6 mt-1">
        <h2 className="title-training">Coach management</h2>
      </div>
      {data.length && <TableCoachManagement data={data} />}
      <div style={{ display: 'flex', justifyContent: 'center', marginTop: '30px' }}>
        <a
          style={{ color: 'white' }}
          className="btn-request-session"
          href={M.cfg.wwwroot + '/theme/mindatlas/pages/request_sessions.php'}>
          Add new session request
        </a>
      </div>
    </div>
  );
};

export default MyCoachingSessionsManagerPage;
