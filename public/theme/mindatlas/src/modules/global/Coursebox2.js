import React from 'react'
import axios from 'axios'

export default class Coursebox extends React.Component {
    constructor(props) {
        super();
        this.state = {

        };
    }

    componentDidMount() {

    }

    render() {
        // const  coursesid  = this.props.id;



        let imgWrapperStyle = {
            backgroundImage: 'url("' + this.props.image + '")',
        }

        let barInnerStyle = {
            width: this.props.progress+'%'
        }
        let brandcolor = M.theme.brand.brandcolor2

        return (
            <div className="coursebox mb-3 p-0">
                <div className="img-wrapper container bg-center-cover" style={imgWrapperStyle} >
                    <div className="course-progress row py-1 px-5">
                        <div className="bar-outter col-9 px-0 bg-dark">
                            <div className="bar-inner bg-color-brand-2" style={barInnerStyle}></div>
                        </div>
                        <div className="progress-text col-3 text-right">{this.props.progress}%</div>
                    </div>
                </div>
                <div className="info-wrapper bg-white px-5 py-3">
                    <div className="name mb-3 font-weight-bold color-deep">{this.props.coursename}</div>
                    <div className="desc mb-3 lh-110" dangerouslySetInnerHTML={{ __html: this.props.summary}} />
                    <div className="clearfix">
                        <div className="row no-gutters">
                            <div className="col-md-5 mb-2">
                                <a href={M.cfg.wwwroot + '/course/view.php?id=' + this.props.id} className="btn-custom bg-color-brand-3 hover-bg-color-brand-1 float-right text-white">ENTER</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        )
    }
}