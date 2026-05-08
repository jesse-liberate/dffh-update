import React from 'react';
import { DropdownMultiple} from 'reactjs-dropdown-component';


const MultipleDropdown = ({ data, onChange, searchString, select }) => {
 console.log(this.props);
  const onChanges = (item) => {
    console.log(item)
  }
  return (
    <DropdownMultiple
      name=""
      title="Select"
      titleSingular="Select"
      list={data}
      onChange={onChanges}
      searchable={searchString}
      select={select}
    />
  );
}

export default MultipleDropdown;