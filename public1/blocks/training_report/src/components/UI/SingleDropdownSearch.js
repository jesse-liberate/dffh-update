import React from 'react';
import { DropdownMultiple, Dropdown } from 'reactjs-dropdown-component';


const SingleDropdown = ({ data, onValueChanged, searchString, select }) => {

  const onChange = (item) => {
    
    onValueChanged(item);
  }

console.log(data);
console.log(searchString);
console.log(select);

  return (
    <Dropdown
      name=""
      title="Select"
      list={data}
      searchable={searchString}
      onChange={onChange}
      select={select}
    />
  );
}

export default SingleDropdown;