import React from 'react';
import SingleDropdownSearch from './UI/SingleDropdownSearch';

const SingleSelector = ({ data, onValueChanged }) => {

  return (
    <div className="tw-my-3 filter dropdown-single-filter">
      <div className="filter-content">
        <SingleDropdownSearch 
          data={data} 
          onValueChanged={onValueChanged}
          />        
      </div>
    </div>
  );
}

export default SingleSelector;
