import React from 'react';

// https://getbootstrap.com/docs/4.0/components/modal/

function Modal(props) {
    const {
        id,
        title,
        body,
        OkText = 'OK',
        OkCallback = () => {
            $(`#${id}`).modal('toggle');
        },
        CloseText = 'Close',
        CloseCallback = () => {},
    } = props;

    return (
        <div
            className="modal fade"
            id={id}
            // tabindex="-1"
            role="dialog"
            aria-labelledby={`${id}-label`}
            aria-hidden="true"
        >
            <div className="modal-dialog" role="document">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title" id={`${id}-label`}>
                            {title}
                        </h5>
                        <button
                            type="button"
                            className="close"
                            data-dismiss="modal"
                            aria-label="Close"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div className="modal-body">
                        {body}
                        {props.children}
                    </div>
                    <div className="modal-footer">
                        <button
                            type="button"
                            className="btn btn-secondary"
                            data-dismiss="modal"
                        >
                            {CloseText}
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={(e) => {
                                OkCallback(e);
                            }}
                        >
                            {OkText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Modal;
