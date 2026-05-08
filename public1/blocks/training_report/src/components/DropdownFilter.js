import React from 'react';
import SingleDropdown from './UI/SingleDropdown';

const DropdownFilter = ({ data, onValueChanged }) => {


  return (
    <div className="dropdown-filter">
      <SingleDropdown 
        data={data} 
        onValueChanged={onValueChanged}/>
    </div>
  );
}

export default DropdownFilter;
