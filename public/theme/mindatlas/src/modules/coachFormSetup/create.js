import '@scss/layouts/mycoachingsessions.scss';
import React, { useReducer, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import TrainingSessionTable from '../global/listCoachFormSetupDetail';
import FieldInput from './fieldInput';

const CoachCreateForm = () => {
  const [data, setData] = useState([]);
  const [formData, setFormData] = useState({});
  const [error, setError] = useState('');
  const [fields, setFields] = useState([
    {
      id: 1,
      field: {
        name: '',
        datatype: 'text',
        defaultdata: '',
        required: '',
        sortorder: '',
        description: '',
        shortname: '',
      },
    },
  ]);
  const createForm = () => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_ajax_create_form',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          ...formData,
        },
      })
      .then((res) => {
        setData(res.data.data);
        console.log('res', res.data.data);
        if (res.data.data.error) {
          setError(res.data.data.message);
        } else setError('');
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('id');
    // getSessions(id);
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((values) => ({ ...values, [name]: value }));
  };

  const onSubmit = (e) => {
    e.preventDefault();
    createForm();
  };

  const createField = (e) => {
    e.preventDefault();
    console.log(fields);
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_add_formfield',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          formid: data.id,
          fields,
        },
      })
      .then((res) => {
        // setData(res.data.data);
        window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder.php';
      })
      .catch((err) => {
        console.log(err);
      });
  };

  return (
    <div id="react-mycoachingsessions">
      <div className="d-flex flex-row justify-content-between mb-3 mt-1">
        <h2 className="title-training">Create new form</h2>
      </div>
      <form onSubmit={onSubmit}>
        <div className="form-group row">
          <label htmlFor="sel1" className="col-sm-2 col-form-label">
            Form name
          </label>
          <div className="col-sm-3">
            <input
              disabled={data.id}
              className="form-control"
              id="sel1"
              name="name"
              value={formData.name || ''}
              onChange={handleChange}
            />
          </div>
        </div>
        <div className="form-group row">
          <label htmlFor="des" className="col-sm-2 col-form-label">
            Description
          </label>
          <div className="col-sm-3">
            <input
              disabled={data.id}
              className="form-control"
              id="des"
              name="description"
              value={formData.description || ''}
              onChange={handleChange}
            />
          </div>
        </div>
        <div className="form-group row">
          <label htmlFor="visible" className="col-sm-2 col-form-label">
            Visible
          </label>
          <div className="col-sm-3">
            <select disabled={data.id} className="form-control" id="visible" name="visible" onChange={handleChange}>
              <option value="0">invisible</option>
              <option value="1">visible</option>
            </select>
          </div>
        </div>
        {!data.id && (
          <div className="form-group row">
            <label htmlFor="visible" className="col-sm-2 col-form-label"></label>
            <div className="col-sm-3">
              <button type="submit" style={{ color: 'white' }} className="btn-request-session px-2 w-100">
                Create form
              </button>
            </div>
          </div>
        )}
        {error && <p style={{ color: 'red' }}>{error}</p>}
      </form>
      {data.id && (
        <>
          <div className="d-flex flex-row justify-content-between mb-3 mt-1">
            <h2 className="title-training">Field of form</h2>
          </div>
          {fields.map((field, idx) => (
            <FieldInput field={field} key={field.id} setFields={setFields} fields={fields} />
          ))}
          <button className="btn-request-session" onClick={createField}>
            Create field
          </button>
        </>
      )}
    </div>
  );
};

export default CoachCreateForm;
