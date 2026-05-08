import React from 'react';
import axios from 'axios';
// import Coursebox from '@modules/global/Coursebox.js'
import Courses from  './components/Courses';
import Pagination from './components/Pagination';

const ENROLLED = 1
const OUTSTANDING = 2
const COMPLETED = 3

export default class SectionCourses extends React.Component {
    constructor(props) {
        super();
        this.state = {
            progress: 0,
            completed: [],
            outstanding: [],
            enrolled: [],
            completed_percent: 0,
            outstanding_percent: 0,
            selectedCourses: [],
            selectedTab: OUTSTANDING,
            currentPage:1,
            coursePerPage: 6
        };

        this.clickTab = this.clickTab.bind(this)
    }

    componentDidMount() {
        // axios.post(`http://localhost:5555/blocks/theme_support/api/index.php`, {
        axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
            action: 'get_user_learning_progress',
            payload: {
                userid: M.user.id,
                sesskey: M.user.sesskey
            },
        }).then(res => {
            console.log(res);
            // console.log(M.user.id);
            // console.log( M.user.sesskey);
            let enrolled = res.data.completed.concat(res.data.not_completed)
            let completed_percent = enrolled.length ? parseInt(res.data.completed.length/enrolled.length)*100 : 0
            let outstanding_percent = enrolled.length ? parseInt(res.data.not_completed.length/enrolled.length)*100 : 0

            this.setState({
                nodeadline: res.data.nodeadline,
                progress: res.data.progress,
                completed: res.data.completed,
                outstanding: res.data.not_completed_deadline,
                enrolled: enrolled,
                completed_percent: completed_percent,
                outstanding_percent: outstanding_percent,
                selectedCourses: res.data.nodeadline === 0 ? res.data.not_completed_deadline : enrolled,
                selectedTab: res.data.nodeadline === 0 ? OUTSTANDING : ENROLLED
            });
        })

    }

    clickTab(e) {
        switch (parseInt(e.currentTarget.dataset.tab)) {
            case ENROLLED:
                this.setState({
                    selectedCourses: this.state.enrolled,
                    selectedTab: ENROLLED,
                    currentPage:1
                })
                break
            case OUTSTANDING: {
                this.setState({
                    selectedCourses: this.state.outstanding,
                    selectedTab: OUTSTANDING,
                    currentPage:1
                })
                break
            }
            case COMPLETED: {
                this.setState({
                    selectedCourses: this.state.completed,
                    selectedTab: COMPLETED,
                    currentPage:1
                })
                break
            }
            default: {
                this.setState({
                    selectedCourses: this.state.enrolled,
                    currentPage:1
                })
                break
            }
        }
    }

    render() {
        const that = this

        const {currentPage, coursePerPage} = this.state;

        const indexOfLastPost = currentPage * coursePerPage;

        const indexOfFirstPost = indexOfLastPost - coursePerPage;

        const currentCourses = this.state.selectedCourses.slice(indexOfFirstPost, indexOfLastPost);

        const paginate = pageNum => this.setState({currentPage: pageNum});

        const nextPage = () => this.setState({currentPage: currentPage + 1});

        const prevPage = () => this.setState({currentPage: currentPage - 1});

        function renderCycleProgress(title, number, progress) {
            let dasharray = 440
            let circleStyle = {
                stroke: M.theme.brand.brandcolor2,
                strokeDasharray: dasharray,
                strokeDashoffset: dasharray - (dasharray * parseInt(progress) / 100),
            }

            return (
                <div className="cycleProgress d-inline-block">
                    <svg>
                        <circle style={circleStyle} cx="72" cy="72" r="70" transform="rotate(-90 72 72)"></circle>
                    </svg>
                    <div className="xy-center">
                        <div className="number display-4 font-weight-bolder">{number}</div>
                        <div className="title lh-100"><small><b>{title}</b></small></div>
                    </div>

                </div>
            )
        }

        function tabClasses(tab) {
            let classes = "col-md-4 tab hover-bg-color-brand-2 p-2 "
            if (tab == that.state.selectedTab) {
                classes += ' selected bg-color-brand-2 '
            } else {
                classes += ' bg-color-brand-3 '
            }
            return classes
        }



        return (
            <section id="section-courses" className="bg-light">
                {/* <div className="container-fluid wrapper-statistic bg-color-primary">
                    <div className="container">
                        <div className="row py-4">
                            <div className="col-md-3"></div>
                            <div className="col-md-2 enrolled text-center mb-3">
                                {renderCycleProgress('ENROLLED COURSES', this.state.enrolled.length, this.state.progress)}
                            </div>
                            <div className="col-md-2 outstanding text-center  mb-3">
                                {renderCycleProgress(
                                    'OUTSTANDING COURSES', 
                                    this.state.outstanding.length, 
                                    this.state.outstanding_percent
                                )}
                            </div>
                            <div className="col-md-2 completed text-center  mb-2">
                                {renderCycleProgress(
                                    'COMPLETED COURSES', 
                                    this.state.completed.length, 
                                    this.state.completed_percent
                                )}
                            </div>
                            <div className="col-md-3"></div>
                        </div>
                    </div>
                </div>
                 */}
                <div className="container-fluid wrapper-statistic bg-light py-4">
                    <div className="container">
                        <div className="course-title color-brand-1 font-weight-bold my-3">My practice elements</div>
                        <div className="tabs row no-gutters text-center text-white">
                            <div 
                                id="tab-outstanding" 
                                data-tab={OUTSTANDING} 
                                className={tabClasses(OUTSTANDING)} 
                                onClick={this.clickTab}
                            >
                                <div className="tooltip-tab"><b>To do</b>
                                    <span className="tooltiptext-1 tooltiptext-todo">My required self-guided learning that is in progress, or not yet started.</span>
                                </div>
                                <div className="triangle-bottom triangle-bottom-color-brand-3 "></div>
                            </div>
                            <div 
                                id="tab-completed" 
                                data-tab={COMPLETED} 
                                className={tabClasses(COMPLETED)} 
                                onClick={this.clickTab}
                            >
                                <div className="tooltip-tab"><b>Completed</b>
                                    <span className="tooltiptext-1 tooltiptext-completed">My required and optional self-guided learning that I have completed.</span>
                                </div>
                                <div className="triangle-bottom triangle-bottom-color-brand-3 "></div>
                            </div>
                            <div 
                                id="tab-enrolled" 
                                data-tab={ENROLLED} 
                                className={tabClasses(ENROLLED)} 
                                onClick={this.clickTab}
                            >
                                <div className="tooltip-tab"><b>All self-guided learning</b>
                                    <span className="tooltiptext-1 tooltiptext-all">A library of all required and optional self-guided learning.</span>
                                </div>
                                <div className="triangle-bottom triangle-bottom-color-brand-3 "></div>
                            </div>
                        </div>
                        <div className="courses-wrapper">
                        {this.state.selectedTab == OUTSTANDING && this.state.nodeadline == 1 ? (
                            <div>
                                <div className="w-100 text-center">You're all caught up!</div>
                                <div className="pb-4 w-100 text-center">Any new required learning assignments will appear here.</div>
                            </div>
                        ) : this.state.selectedCourses.length ? (
                            <Courses courses={currentCourses}></Courses>
                        ) : (
                            <div className="py-4 w-100 text-center">
                                {this.state.selectedTab == OUTSTANDING ? 
                                <div>
                                    <div className="w-100 text-center">You're all caught up!</div>
                                    <div className="pb-4 w-100 text-center">Any new required learning assignments will appear here.</div>
                                </div> : ''}
                                {this.state.selectedTab == COMPLETED ? 'NO COMPLETED COURSES' : ''}
                                {this.state.selectedTab == ENROLLED ? 'NO ENROLLED COURSES' : ''}
                            </div>
                        )}
                        {this.state.selectedTab != OUTSTANDING || this.state.nodeadline != 1 ? (
                            this.state.selectedCourses.length ? (
                                <Pagination
                                    coursePerPage={coursePerPage}
                                    totalCourses={this.state.selectedCourses.length}
                                    paginate={paginate}
                                    nextPage={nextPage}
                                    prevPage={prevPage}
                                    currentPage={currentPage}
                                ></Pagination>
                            ) : (
                                ''
                            )
                        ) : (
                            ''
                        )}
                            {/* <div className="all-courses-link">
                                <a href={M.cfg.wwwroot + "/course"} className="btn btn-primary float-right">ALL COURSES</a>
                            </div> */}
                            
                        </div>
                        </div>
                        
                        
                </div>
            </section>
        )
    }
}