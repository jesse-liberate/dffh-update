import React from 'react';
import DropdownTreeSelect from 'react-dropdown-tree-select'
// import 'react-dropdown-tree-select/dist/styles.css'
//https://github.com/dowjones/react-dropdown-tree-select
function getAllSelectedIds(selectedNodes) {
  return selectedNodes;
}

const SingleTree =  React.memo(({ data, onValueChanged }) => {

  
  const onNodeSelected = (currentNode, selectedNodes) => {
    // console.log('onChange::', currentNode, selectedNodes)
    const newSelected = getAllSelectedIds(selectedNodes);
    // console.log(newSelected);
    onValueChanged(newSelected);
  }

  function onAction(){

  }

  function onNodeToggle(){

  }
  return (
    <DropdownTreeSelect 
      data={data} 
      onChange={onNodeSelected} 
      onAction={onAction} 
      onNodeToggle={onNodeToggle}
      mode='radioSelect'
      />
  );
})

export default SingleTree;
