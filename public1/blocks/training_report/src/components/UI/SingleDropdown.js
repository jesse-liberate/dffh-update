import React from 'react';
import { DropdownMultiple, Dropdown } from 'reactjs-dropdown-component';


const SingleDropdown = ({ data, onValueChanged, select }) => {

  // onValueChanged = (item, name) => {
    
  // }
  // const onNodeSelected = (currentNode, selectedNodes) => {
  //   // console.log('onChange::', currentNode, selectedNodes)
  //   const newSelected = getAllSelectedIds(selectedNodes);
  //   // console.log(newSelected);
  //   onValueChanged(newSelected);
  // }
  const onChange = (item) => {
    
    onValueChanged(item);
  }

  return (
    <Dropdown
      name=""
      title="Select"
      list={data}
      onChange={onChange}
      select={select}
    />
  );
}

export default SingleDropdown;