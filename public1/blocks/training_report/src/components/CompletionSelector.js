import React from 'react';
import SingleDropdown from './UI/SingleDropdown';

const CompletionSelector = ({ completion, onValueChanged }) => {

  return (
    <div className="tw-my-3 filter completion-filter">
      <div className="color-brand-1 font-weight-bold">Completion</div>
      <div className="filter-content">
        <SingleDropdown 
          data={completion} 
          onValueChanged={onValueChanged}/>        
      </div>
    </div>
  );
}

export default CompletionSelector;
