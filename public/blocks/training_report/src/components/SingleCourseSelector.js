import React from 'react';
import SingleDropdownSearch from './UI/SingleDropdownSearch';

const SingleCourseSelector = ({ courses, onValueChanged }) => {

  return (
    <div className="tw-my-3 filter courses-filter">
      <div className="color-brand-1 font-weight-bold">Course  <span className="tw-text-red-500">*</span></div>
      <div className="filter-content">
        <SingleDropdownSearch 
          data={courses} 
          onValueChanged={onValueChanged}
          searchString={["Search for course", "No matching course"]}
          />        
      </div>
    </div>
  );
}

export default SingleCourseSelector;
