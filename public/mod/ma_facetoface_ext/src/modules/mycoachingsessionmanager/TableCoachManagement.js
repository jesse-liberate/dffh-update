import React, { useEffect, useState, useReducer } from 'react';

const TableCoachManagement = (data) => {
  return (
    <>
      <></>
      <table className="training-table">
        <tbody>
          <tr className="training-table-header">
            <td>COACH NAME</td>
            <td>AGENCY</td>
            <td>NUMBER OF REQUESTED COACHING SESSIONS</td>
            <td style={{ width: '20%', textAlign: 'center' }}>ACTION</td>
          </tr>
          {data.data.map((item) => (
            <tr key={item.id} className="training-table-body">
              <td>
                <a
                  href={M.cfg.wwwroot + '/theme/mindatlas/pages/requested-training-sessions.php?coach_id=' + item.id}
                  style={{ color: 'black' }}>
                  {`${item.firstname} ${item.lastname}`}{' '}
                </a>
              </td>
              <td> {item.agency}</td>
              <td> {item.total}</td>
              <td style={{ width: '20%', textAlign: 'center' }}>
                <a
                  className="btn-request-session btn"
                  href={M.cfg.wwwroot + '/theme/mindatlas/pages/requested-training-sessions.php?coach_id=' + item.id}>
                  View
                </a>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
};
export default TableCoachManagement;
