import React from 'react';

const ListCoachFormSetupDetail = ({ data, handleDisable }) => {
  return (
    <>
      <table className="training-table">
        <tbody>
          <tr className="training-table-header">
            <td>FROM NAME</td>
            <td>FIELD TYPE</td>
            <td>DEFAULT DATA</td>
            <td>SORT ORDER</td>
          </tr>
          {data.map((item) => (
            <tr key={item.field.id} className="training-table-body">
              <td> {item.field.name} </td>
              <td> {item.field.datatype}</td>
              <td> {item.field.defaultdata}</td>
              <td> {item.field.sortorder}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
};
export default ListCoachFormSetupDetail;
