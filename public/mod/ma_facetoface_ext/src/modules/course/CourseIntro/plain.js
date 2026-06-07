import React from 'react'
import axios from 'axios'

export default class CourseIntro extends React.Component {
    constructor(props) {
        super();
        this.state = {
            image:'',
            fullname: '',
            summary: '',
            userId: M.user.id,
        };
    }

    componentDidMount() {
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
        })
    }

    render() {
        return (
            <div className="course-intro my-3">
                <div className="summary mb-3" dangerouslySetInnerHTML={{ __html: this.state.summary}} />
            </div>            
        )
    }
}