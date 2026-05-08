import React from 'react';
import DropdownTreeSelect from "react-dropdown-tree-select";



function getAllSelectedIds(selectedNodes) {
  // console.log(selectedNodes)
  // console.log(data)
  
  return selectedNodes.reduce((all, node) => {
    all.push(node.value);
    console.log(all)
    return all
  }, []);

}

const SingleLevelTree = React.memo(({ data, onValueChanged }) => {

  const onNodeSelected = (currentNode, selectedNodes) => {
    console.log('onChange:', currentNode, selectedNodes)
    const newSelected = getAllSelectedIds(selectedNodes);
    onValueChanged(newSelected);
  }

  function onAction(){

  }

  function onNodeToggle(){

  }

  // console.log('SingleLevelTree render');

  return (
    <DropdownTreeSelect 
      data={data} 
      onChange={onNodeSelected} 
      onAction={onAction} 
      onNodeToggle={onNodeToggle}
      />
  );
})

export default SingleLevelTree;
