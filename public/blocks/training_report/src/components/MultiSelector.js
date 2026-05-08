import React from 'react';
import SingleLevelTree from './UI/SingleLevelTree';

const MultiSelector = ({ data, onValueChanged }) => {
  return (
    <div className="tw-my-3 filter dropdown-multi-filter">
      <div className="filter-content">
        <SingleLevelTree 
          data={data} 
          onValueChanged={onValueChanged}/>        
      </div>
    </div>
  );
}

export default MultiSelector;
