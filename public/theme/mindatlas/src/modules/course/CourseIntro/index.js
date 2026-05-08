import React from 'react'
import axios from 'axios'
import StarRatings from 'react-star-ratings';
import { parse } from 'semver';
export default class CourseIntro extends React.Component {
    constructor(props) {
        super();
        this.state = {
            image:'',
            fullname: '',
            rating: '',
            summary: '',
            showRateZone: false,
            userId: M.user.id,
            ratetimes: '',
            viewtimes: '',
            userrate: '',
            courseRatePlugin: M.theme.plugins.courserating
            // courseRatePlugin: true
        };
        this.rateCourse = this.rateCourse.bind(this)
        this.changeRating = this.changeRating.bind(this)
    }

    componentDidMount() {
        //example without payload, please check blocks/theme/support/api/courses.php
        //example with payload, please check blocks/theme/support/api/courses.php
        // axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/courses.php`, {
        //     action: 'get_course_info',
        //     payload: {
        //         courseId: this.props.id
        //     },
        // }).then(res => {
        //     console.log(res.data);
        // }),

        axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/courses.php`, {
            action: 'get_course_info',
            payload: {
                courseId: this.props.id,
                sesskey: M.user.sesskey
            },
        }).then(res => {
            console.log(res.data);
            this.setState({
                image: res.data.image,
                fullname: res.data.fullname,
                summary: res.data.summary,
            });
        }),

        // axios.post(`${M.cfg.wwwroot}/blocks/most_viewed_liked_course/api/index.php`, {
        //     action: 'get_course_rate',
        //     payload: {
        //         courseId: this.props.id,
        //         userId: this.state.userId,
        //         sesskey: M.user.sesskey
        //     },
        // }).then(res => {
        //     console.log(res.data);
        //     this.setState({
        //         rating: res.data.rating,
        //         ratetimes: res.data.ratetimes,
        //         viewtimes: res.data.viewtimes,
        //         userrate: res.data.userrate
        //     });
        // })

        // axios.post(`${M.cfg.wwwroot}/blocks/most_viewed_liked_course/api/index.php`, {
        //     action: 'view_course',
        //     payload: {
        //         courseId: this.props.id,
        //         userId: this.state.userId
        //     },
        // })


    }
    rateCourse(){
        this.setState({
            showRateZone : true
        });
    }

    changeRating(newRating, name){
        const courseId = this.props.id;
        const userId = M.user.id;
        // console.log(courseId + ' course');
        // console.log(userId + ' user');
        // console.log(newRating + ' newRating');
        
        axios.post(`${M.cfg.wwwroot}/blocks/most_viewed_liked_course/api/index.php`, {
            action: 'rate_course',
            payload: {
                courseId: courseId,
                userId: userId,
                rate: newRating,
                sesskey: M.user.sesskey
            },
        }).then(res => {
            // console.log(res.data);
            this.setState({
                rating: res.data.rating,
                ratetimes: res.data.ratetimes,
                viewtimes: res.data.viewtimes,
                userrate:  res.data.userrate,
            });
        })
    }

    render() {

        let imgWrapperStyle = {
            backgroundImage: 'url("' + this.state.image + '")',
        }
        let brandcolor = M.theme.brand.brandcolor2;

        let rate = Number(this.state.rating);

        let userrate = Number(this.state.userrate) != '' ?   Number(this.state.userrate): 0;
        console.log(rate);
        
        return (
            <div className="course-intro my-3">
                {/* <h1>Course Intro</h1> */}
                <div className="row no-gutters ">
                    <div className="course-image col-md-4 my-3" style={imgWrapperStyle}></div>
                    <div className="course-details col-md-8 px-3 my-3">
                        <div className="fullname mb-3 font-weight-bold color-deep"><span>{this.state.fullname}</span></div>
                        {
                            this.state.courseRatePlugin ? 
                            <div className="row no-gutters ">
                                <div className="col-md-3 rating mb-3">
                                    <StarRatings
                                    rating={rate}
                                    starRatedColor={brandcolor}
                                    numberOfStars={5}
                                    starDimension="15px"
                                    starSpacing="1px"
                                    />
                                    <span className="rate-text ml-3 align-middle font-weight-bold color-primary">{this.state.rating}</span>
                                </div>
                                <div className="col-md-5 mb-3">
                                    <span className="rate-info font-weight-bold color-primary">BASED ON {this.state.ratetimes} USER RATINGS | {this.state.viewtimes} VIEWS</span>
                                </div>
                                <div className="col-md-4 mb-3">
                                { this.state.userrate == '' && this.state.showRateZone == false ?
                                <div className="btn-custom bg-color-brand-3 float-right hover-bg-color-brand-1 text-white"  onClick={this.rateCourse}>ADD YOUR RATING</div>
                                :
                                <div className="user-rate float-right">
                                    <span className="your-rate font-weight-bold color-primary">
                                        YOUR RATE: &nbsp;&nbsp;
                                    </span>
                                    <StarRatings
                                        rating={userrate}
                                        starRatedColor={brandcolor}
                                        starHoverColor={brandcolor}
                                        numberOfStars={5}
                                        starDimension="15px"
                                        starSpacing="1px"
                                        changeRating={this.changeRating}
                                        name='your rate'
                                    />
                                </div> 
                               
                                }
                                
                            </div>
                            </div>
                        :
                            ''
                        }
                        <div className="summary mb-3" dangerouslySetInnerHTML={{ __html: this.state.summary}} />

                        {/* {this.state.summary}</div> */}

                    </div>
                </div>
            </div>            
        )
    }
}