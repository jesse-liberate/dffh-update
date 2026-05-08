import React, { useEffect, useState } from 'react';
import axios from 'axios';



export default function LearningProgress() {
    const [progress, setProgress] = useState(null);
    const [completed, setCompleted] = useState(null);
    const [not_completed, setNot_completed] = useState(null);
    const [nodeadline, setNodeadline] = useState(null);


    useEffect(() => {
        axios
            .post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
                action: 'get_user_learning_progress',
                payload: {
                    userid: M.user.id,
                    sesskey: M.user.sesskey,
                },
            })
            .then((res) => {
                setProgress(res.data.progress);
                setCompleted(res.data.completed);
                setNot_completed(res.data.not_completed);
                setNodeadline(res.data.nodeadline);
            });
    }, []);


    return (
        <div>
            <div className="learning-progress align-items-center mb-4 section-title h1"
            style={{ display: nodeadline === 1 ? 'none' : 'block' }}>
                <div style={{fontWeight: '500'}} className="h3 mb-3 color-deep title">
                    My learning progress
                </div>
                <div className="progressbar-wrapper row">
                    <div className="col-md-1 text font-weight-bold color-brand-1">
                        eeee1{progress}%
                    </div>
                    <div className="col-md-11">
                        <div className="progressbar">
                            <div
                                className="bar-inner bg-color-primary"
                                style={{ width: `${progress}%` }}
                            ></div>
                            {/* <div className="text xy-center">{progress}%</div> */}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
