import React from 'react';
import SingleDropdown from './UI/SingleDropdown';

const SuspendedSelector = ({ suspended, onValueChanged, select }) => {

  return (
    <div className="tw-my-3 filter suspended-filter">
      <div className="color-brand-1 font-weight-bold">Resigned</div>
      <div className="filter-content">
        <SingleDropdown 
          data={suspended} 
          onValueChanged={onValueChanged}
          select={select}/>        
      </div>
    </div>
  );
}

export default SuspendedSelector;
