import '@scss/layouts/mycoachingsessions.scss';
import React, { Fragment } from 'react';

const PreviewForm = ({ data, setPreview }) => {
  return (
    <div className="modal" tabIndex="-1" role="dialog" style={{ display: 'block' }}>
      <div className="modal-dialog" role="document">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title" id="exampleModalLabel">
              {data?.formSetup?.name}
            </h5>
            <button
              type="button"
              className="close"
              data-dismiss="modal"
              aria-label="Close"
              onClick={() => setPreview(false)}>
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div className="modal-body">
            <form>
            <div className="preview-modal"
              dangerouslySetInnerHTML={{ __html:   data.html }}
                        />
            </form>
          </div>
          <div className="modal-footer">
            <button type="button" className="btn btn-secondary" data-dismiss="modal" onClick={() => setPreview(false)}>
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PreviewForm;
