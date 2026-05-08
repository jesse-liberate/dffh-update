import React from 'react';
import MultipleDropdown from './UI/MultipleDropdown';
const MultiUserSelector = ({ users, onChange, select }) => {
  return (
    <div className="tw-my-3 filter courses-filter">
      <div className="color-brand-1 font-weight-bold"> Users <span className="tw-text-red-500">*</span></div>
      <div className="filter-content">
        <MultipleDropdown 
          data={users} 
          onChange={onChange}
          searchString={["Search for users", "No matching user"]}
          select={select}
          />        
      </div>
    </div>
  );
}

export default MultiUserSelector;
