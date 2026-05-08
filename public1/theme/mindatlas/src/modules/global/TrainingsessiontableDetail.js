import React, { useEffect, useState, useReducer } from 'react';

const TrainingSessionTable = (data) => {
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
  return (
    <>
      <></>
      <table className="training-table">
        <tbody>
          <tr className="training-table-header">
            <td>User name</td>
            <td>date</td>
            <td>time</td>
            <td>options</td>
          </tr>
          {data.data.map((item) => (
            <tr key={item.request_id} className="training-table-body">
              <td> {`${item.firstname} ${item.lastname}`} </td>
              <td> {item.date}</td>
              <td>{item.time}</td>
              <td>
                <a
                  className="training-button"
                  style={{ fontWeight: 'bold' }}
                  href={
                    M.cfg.wwwroot +
                    '/theme/mindatlas/pages/detail-requested-training-sessions.php?request=' +
                    item.request_id +
                    '&courseId=' +
                    item.courseid
                  }>
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
export default TrainingSessionTable;
