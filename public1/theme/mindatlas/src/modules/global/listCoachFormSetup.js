import React, { useEffect, useState, useReducer } from 'react';
import moment from 'moment';

const ListCoachFormSetup = ({ data, handleRemove }) => {
  return (
    <>
      <></>
      <table className="training-table">
        <tbody>
          <tr className="training-table-header">
            <td>FORM NAME</td>
            <td>NUMBER OF FIELD</td>
            <td>CREATED DATE</td>
            <td>MODIFIED DATE</td>
            <td style={{ textAlign: 'center' }}>OPTION</td>
          </tr>
          {data.map((item) => (
            <tr key={item.id} className="training-table-body">
              <td> {item.name} </td>
              <td> {item.total_field}</td>
              <td>{moment.unix(item.timecreated).format('Y/M/D h:mm A')}</td>
              <td>{moment.unix(item.timeupdated).format('Y/M/D h:mm A')}</td>
              <td className="d-flex justify-content-center">
              <a
                  className="btn-request-session training-button bg-primary mx-3"
                  style={{ cursor: 'pointer' }}
                  href={M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder-detail.php?id=' + item.id}>
                  View
                </a>
                <a
                  className="btn-request-session training-button bg-warning mx-3"
                  style={{ cursor: 'pointer' }}
                  href={M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder-edit.php?id=' + item.id}>
                  Edit
                </a>
                <a
                  className="btn-request-session training-button bg-danger mx-3"
                  onClick={() => handleRemove(item.id)}
                  style={{ cursor: 'pointer', padding: '10px 46px',width:'149px' }}>
                  Remove
                </a>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
};
export default ListCoachFormSetup;
