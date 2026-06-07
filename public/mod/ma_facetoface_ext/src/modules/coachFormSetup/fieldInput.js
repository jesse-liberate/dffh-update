import React, { useEffect, useState } from 'react';
import Select from 'react-select'
import axios from 'axios';
import makeAnimated from 'react-select/animated';
const animatedComponents = makeAnimated();
const dataType = [
  { value: 'text', label: 'Text' },
  { value: 'textarea', label: 'Textarea' },
  { value: 'checkbox', label: 'Checkbox' },
  { value: 'radio', label: 'Radio' },
  { value: 'select', label: 'Select' },
  // { value: 'file', label: 'File' },
  // { value: 'email', label: 'Email' },
  // { value: 'number', label: 'Number' },
  // { value: 'password', label: 'Password' },
  // { value: 'hidden', label: 'Hidden' },
  // { value: 'submit', label: 'Submit' },
];

const FieldInput = ({ field, setFields, fields }) => {
 
  const [dependencies, setDependencies] = useState([]);
  const [staff, setStaff] = useState(null);
  const queryParams = new URLSearchParams(window.location.search);
  const id = queryParams.get('id');
  const setDependent = () => {
    axios
    .post(M.cfg.wwwroot + '/theme/mindatlas/api/index.php', {
      action: 'dffh_ajax_get_available_fields',
      payload: {
        formid: id,
        fieldid: field.id
      },
    })
    .then((res) => {
      setDependencies(res.data.data);
    })
    .catch((err) => {
      console.log(err);
    });
};
useEffect(() => {
  setDependent();
}, [FieldInput]);
  const handleChange = (e, id) => {
    const { name, value } = e.target;
    setFields((fields) => {
      const newFields = fields.map((field) => {
        if (field.id === id) {
          return { ...field, field: { ...field.field, [name]: value } };
        }
        return field;
      });
      return newFields;
    });
  };
  const handleRemove = (id) => {
    setFields((d) => {
      const newFields = d.filter((field) => field.id !== id);
      return newFields;
    });
  };
  const handleStaffChange = (event) => {
    console.log( event);
    setStaff(event);
    field.field.param1 = event.value;
   
  }
  const addField = (e) => {
    e.preventDefault();
    setFields([
      ...fields,
      {
        id: fields.length + 1,
        field: { name: '', datatype: 'text', defaultdata: '', required: '', sortorder: '', description: '' },
      },
    ]);
  };
  return (
    <form>
      <div className="form-row">
        <div className="form-group col-md-2">
          <label htmlFor="inputCity">Name</label>
          <input
            onChange={(e) => handleChange(e, field.id)}
            type="text"
            name="name"
            value={field.field.name}
            className="form-control"
            id="inputCity"
          />
        </div>
        <div className="form-group col-md-2">
          <label htmlFor="inputCity">Short name</label>
          <input
            onChange={(e) => handleChange(e, field.id)}
            type="text"
            name="shortname"
            value={field.field.shortname}
            className="form-control"
            id="inputCity"
          />
        </div>
        <div className="form-group col-md-2">
          <label htmlFor="inputState">Data type</label>
          <select
            onChange={(e) => handleChange(e, field.id)}
            id="inputState"
            className="form-control"
            name="datatype"
            value={field.field.datatype}>
            {dataType.map((item, index) => (
              <option key={index} value={item.value}>{item.label}</option>
            ))}
          </select>
        </div>
        <div className="form-group col-md-2">
          <label htmlFor="inputZip">Default data</label>
          <textarea
            onChange={(e) => handleChange(e, field.id)}
            className="form-control"
            rows={1}
            id="inputZip"
            name="defaultdata"
            value={field.field.defaultdata}
          />
        </div>
        <div className="form-group col-md-1 d-none">
          <label htmlFor="inputState">Required</label>
          <select
            onChange={(e) => handleChange(e, field.id)}
            id="inputState"
            className="form-control"
            name="required"
            value={field.field.required}>
            <option value="0" selected>
              No
            </option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div className="form-group col-md-1">
          <label htmlFor="inputCity">SortOrder</label>
          <input
            onChange={(e) => handleChange(e, field.id)}
            type="text"
            name="sortorder"
            className="form-control"
            id="inputCity"
            value={field.field.sortorder}
          />
        </div>
        <div className="form-group col-md-2">
          <label htmlFor="inputZip">Dependency </label>
          <select
           onChange={(e) => handleChange(e, field.id)}
            id="dependencies"
            className="form-control"
            name="param1"
            value={field.field.param1}>
            {dependencies.map((item, index) => (
              <option key={index} value={item.value}>{item.label}</option>
            ))}
          </select>
        </div>
        <div className="form-group col-md-1 d-flex align-items-end">
          <button onClick={addField} type="button" className="btn btn-success">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="currentColor"
              className="bi bi-plus-circle-fill"
              viewBox="0 0 16 16">
              <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z" />
            </svg>
          </button>
          {fields.length > 1 && (
            <button type="button" className="btn btn-danger" onClick={() => handleRemove(field.id)}>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                className="bi bi-file-minus-fill"
                viewBox="0 0 16 16">
                <path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM6 7.5h4a.5.5 0 0 1 0 1H6a.5.5 0 0 1 0-1z" />
              </svg>
            </button>
          )}
        </div>
      </div>
    </form>
  );
};

export default FieldInput;
