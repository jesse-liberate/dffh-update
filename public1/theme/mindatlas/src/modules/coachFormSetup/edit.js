import '@scss/layouts/mycoachingsessions.scss';
import axios from 'axios';
import React, { useEffect, useState } from 'react';
import FieldInput from './fieldInput';

const CoachEditForm = () => {
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
  const getSessions = (id) => {
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_ajax_view_formbuilder',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          formid: id,
        },
      })
      .then((res) => {
        setFormData(res.data.data.formSetup);
        setFields(res.data.data.fieldForm);
        console.log('res', res.data.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };
  useEffect(() => {
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('id');
    getSessions(id);
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((values) => ({ ...values, [name]: value }));
  };

  const onSubmit = (e) => {
    e.preventDefault();
    createForm();
  };

  const UpdateForm = (e) => {
    e.preventDefault();
    const queryParams = new URLSearchParams(window.location.search);
    const id = queryParams.get('id');
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_edit_formfield',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          formid: id,
          fields,
          formData,
        },
      })
      .then((res) => {
       window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder.php';
      })
      .catch((err) => {
        console.log(err);
      });
  };

  return (
    <div id="react-mycoachingsessions">
      <div className="d-flex flex-row justify-content-between mb-3 mt-1">
        <h2 className="title-training">Update form: {formData.name}</h2>
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
            <select
              value={formData.visible}
              disabled={data.id}
              className="form-control"
              id="visible"
              name="visible"
              onChange={handleChange}>
              <option value="0">invisible</option>
              <option value="1">visible</option>
            </select>
          </div>
        </div>
      </form>
      <div className="d-flex flex-row justify-content-between mb-3 mt-1">
        <h2 className="title-training">Form fields</h2>
      </div>
      {fields.map((field, idx) => {
        return <FieldInput field={field} key={field.id} setFields={setFields} fields={fields} />;
      })}
      <div className="margin-top-form">
        <button className="btn-request-session mr-3" onClick={UpdateForm}>
          Update form
        </button>
        <a
          href={M.cfg.wwwroot + '/theme/mindatlas/pages/form-builder.php'}
          className="btn-request-session btn btn-secondary"
          style={{ background: 'gray' }}>
          Cancel
        </a>
      </div>
    </div>
  );
};

export default CoachEditForm;
