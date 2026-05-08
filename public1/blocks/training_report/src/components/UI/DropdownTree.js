import React from 'react';
import DropdownTreeSelect from 'react-dropdown-tree-select'
// import 'react-dropdown-tree-select/dist/styles.css'


const dropDownTree = (props) => {
      <DropdownTreeSelect 
        data={props.data} 
        onChange={props.changed} 
        onAction={props.actioned} 
        onNodeToggle={props.nodeToggled}/>
}
export default dropDownTree