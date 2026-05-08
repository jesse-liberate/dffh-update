import React, { Component } from 'react'
import Coursebox from '@modules/global/Coursebox.js'
import Pagination from './Pagination.js'
// import Courserate from '@modules/global/Courserate.js';

export class Courses extends Component {

    render(){
        const { courses } = this.props;
        return (
            <div className="row courses-wrapper">
              
                {courses.map(course => (
                    <div className="col-md-4" key={course.course_id}>
                        <Coursebox
                            id={course.course_id}
                            coursename={course.coursename}
                            summary={course.summary}
                            image={course.course_image}
                            progress={course.progress}
                            // rate={course.rate}
                        >

                        </Coursebox>
                </div>
                ))}
                
            </div>
            
        )
    }


}

export default Courses;

