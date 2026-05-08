import React from 'react';
import Tree from './UI/Tree';

const CourseSelector = ({ courses, onValueChanged }) => {
  return (
    <div className="tw-my-3 filter course-filter">
      <div className="color-brand-1 font-weight-bold">Practice element/module<span className="tw-text-red-500">*</span></div>
      <div className="filter-content">
        <Tree 
          id = 'course-filter-tree' 
          data={courses} 
          onValueChanged={onValueChanged}/>        
      </div>
    </div>
  );
}

export default CourseSelector;
