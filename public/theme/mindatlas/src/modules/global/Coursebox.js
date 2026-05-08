import React from 'react'
// import StarRatings from 'react-star-ratings';
// import StarRating from 'react-star-rating'
// import Ratings from 'react-ratings-declarative';
// import 'node_modules/react-star-rating/dist/css/react-star-rating.min.css'
import axios from 'axios'

export default class Coursebox extends React.Component {
    constructor(props) {
        super();
        this.state = {
            courseRatePlugin: M.theme.plugins.courserating,
            rate : 0
            // courseRatePlugin: true
        };
        // this.handleClick = this.handleClick.bind(this);
    }

    componentDidMount() {
        // axios.post(`${M.cfg.wwwroot}/blocks/most_viewed_liked_course/api/index.php`, {
        //     action: 'get_course_rate_by_id',
        //     payload: {
        //         courseId: this.props.id,
        //         sesskey: M.user.sesskey
        //     },
        // }).then(res => {
        //     // console.log(res.data);
        //     this.setState({
        //         rate: res.data
        //     });
        // })
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
                <div className="img-wrapper bg-center-cover" style={imgWrapperStyle} >
                    <div className="course-banner-under">
                    </div>
                    <div className="course-progress row no-gutters py-1 px-5">
                        <div className="bar-outter col-10 px-0 bg-white">
                            <div className="bar-inner bg-color-brand-2" style={barInnerStyle}></div>
                        </div>
                        <div className="progress-text col-2 text-right font-weight-bold">{this.props.progress}%</div>
                    </div>
                </div>
                <div className="info-wrapper bg-white px-5 py-3">
                    <div className="name mb-3 font-weight-bold color-deep">{this.props.coursename}</div>
                    <div className="desc mb-3 lh-110" dangerouslySetInnerHTML={{ __html: this.props.summary}} />
                    <div className="clearfix">
                        <div className="row no-gutters">
                            {
                                this.state.courseRatePlugin == true ?
                                    <div className="col-md-7 rating mb-2">
                                        <StarRatings
                                        rating={this.state.rate}
                                        starRatedColor={brandcolor}
                                        numberOfStars={5}
                                        starDimension="13px"
                                        starSpacing="1px"
                                        />
                                        <span className="ml-3 align-middle font-weight-bold color-primary rate-text">{this.state.rate > 0 ? this.state.rate : ''}</span>
                                    </div>
                                    :
                                    ''
                            }
                            
                            <div className="col-md-5 mb-2">
                                <a href={M.cfg.wwwroot + '/course/view.php?id=' + this.props.id} className="btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold">Start</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        )
    }
}