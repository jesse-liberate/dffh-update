import '@scss/layouts/mycoachingsessions.scss';
import React, { useReducer, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import TrainingSessionTable from '../global/listCoachFormSetupDetail';
import PreviewForm from './components/preview';

const DetailCoachFormSetup = () => {
  const [data, setData] = useState([]);

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
        setData(res.data.data);
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

  console.log('data', data);
  const [preview, setPreview] = useState(false);

  const handleDisable = (id) => {
    const queryParams = new URLSearchParams(window.location.search);
    const idForm = queryParams.get('id');
    axios
      .post(M.cfg.wwwroot + '/blocks/mindatlas_formbuilder/api/index.php', {
        action: 'dffh_ajax_remove_formbuilder',
        payload: {
          userid: M.user.id,
          sesskey: M.user.sesskey,
          field_id: id,
        },
      })
      .then((res) => {
        getSessions(idForm);
        console.log('res', res.data);
      })
      .catch((err) => {
        console.log(err);
      });
  };

  if (!data) return null;
  return (
    <div id="react-mycoachingsessions">
      <div className="d-flex flex-row justify-content-between mb-3 mt-1">
        <h2 className="title-training">{data?.formSetup?.name}</h2>
        <div className="form-group">
          <button style={{ color: 'white' }} className="btn-request-session" onClick={() => setPreview(true)}>
            Preview
          </button>
        </div>
      </div>
      {data && data.fieldForm && data.fieldForm.length && (
        <TrainingSessionTable data={data.fieldForm} handleDisable={handleDisable}></TrainingSessionTable>
      )}
      {preview && <PreviewForm data={data} setPreview={setPreview} />}
    </div>
  );
};

export default DetailCoachFormSetup;
