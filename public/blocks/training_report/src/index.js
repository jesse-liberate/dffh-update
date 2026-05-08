console.log('training report plugin.js is loaded')

import React from 'react';
import ReactDOM from 'react-dom';


function Tile(props) {
    return (
        <div className="tw-mb-4">
            <div className="tw-p-3 tw-shadow-md tw-bg-gray-100 row">
                <div className="">
                    <h1 className="tw-font-bold">{props.name}</h1>
                    <hr />
                    <div>{props.desc}</div>                 
                </div>
                <div className="">
                    <a className="btn btn-primary btn-lg tw-mt-4 float-right" href={M.cfg.wwwroot+props.link} role="button">Open</a>                
                </div>
            </div>

        </div>
    )
}


const jsx = (
    <div id="react-training-report-index" className="container">
        <div className="">
            {/* <Tile name="General report"     link="/blocks/training_report/pages/reports/general.php" desc="Display overall status of selected courses." /> */}
            <Tile name="SGL element summary"    link="/blocks/training_report/pages/reports/activity.php" desc="Display status of selected SGL elment." />
            <Tile name="SGL & Training module report" link="/blocks/training_report/pages/reports/courseoverview.php" desc="Display status of selected SGL & Training module report." />
            <Tile name="Individual report" link="/blocks/training_report/pages/reports/individual.php" desc="Display status of selected hierarchy nodes." />
            <Tile name="User report" link="/blocks/training_report/pages/reports/user.php" desc="Display status of selected users." /> 
            <Tile name="Coaching report" link="/blocks/training_report/pages/reports/coaching.php" desc="Display coaching data of selected users." />    
            <Tile name="Report Settings" link="/admin/settings.php?section=blocksettingtraining_report" desc="Report Settings" />    
        </div>
    </div>
)

ReactDOM.render(jsx, document.getElementById('mount-react-training-report-index'));