import React from 'react';
// import DropdownTreeSelect from 'react-dropdown-tree-select'
import DropdownTreeSelectHOC from "./HOC";
// import 'react-dropdown-tree-select/dist/styles.css'
//https://github.com/dowjones/react-dropdown-tree-select
function getChildrenIds(arr,node,idx){
  // console.log(arr)
  // console.log(node)
  // console.log(idx)
  if(node.type=='course'){
    arr.push(node.value)
  }else if(node.type=='category'){
    // console.log(node.children)
    if(node.children.length){
        node.children.forEach((nextChildren,index)=>{
          getChildrenIds(arr, nextChildren,index)
        }
      )
    }
  }
  return arr
}

function getChildrenIdsV2(cat, indexedCategories) {
  const catNode = indexedCategories[cat.value];
  if (!catNode) {
    return [];
  }
  return catNode.children.reduce((allChildren, child) => {
    if (child.type === 'course') {
      allChildren.push(child.value);
    } else {
      allChildren = allChildren.concat(getChildrenIdsV2(child, indexedCategories));
    }
    return allChildren;
  }, []);
}


function getAllSelectedIds(selectedNodes,data, indexedCategories) {
  // console.log(selectedNodes)
  // console.log(data)
  
  return selectedNodes.reduce((all, node) => {
    if (node.type === 'course') {
      all.push(node.value);
    } else {
      all = all.concat(getChildrenIdsV2(node, indexedCategories));
    }
    return all
  }, []);

  // const selectedIds = selectedNodes.reduce((pre, node)=> {
  //     if(node.type==='course'){
  //       pre.push(node.value)
  //     }
  //     else if(node.type==='category'){
  //       // data[node.path.replace(/\[|]/g,'')].children.forEach((firstChild,idx) => {
  //         console.log(node.path.replace(/['"]+/g, ''));

  //         data[node.path.replace(/['"]+/g, '')].children.forEach((firstChild,idx) => {
  //         // if(firstChild.type=='course'){
  //         //   pre.push(firstChild.value)
  //         // }else if(firstChild.type=='category'){
  //         //   pre = getChildrenIds(pre,firstChild)
  //         // }
  //         getChildrenIds(pre, firstChild,idx)
  //       }
  //       )
  //     }
  //     return pre
  // }, [])
  // return selectedIds;
}

const Tree = React.memo(({id, data, onValueChanged }) => {

  const indexedCategories = {};
  const indexCategory = (cat) => {
    indexedCategories[cat.value] = cat;
    if (cat.children) {
      cat.children.forEach((child) => {
        if (child.type === 'category') {
          indexCategory(child);
        }
      });
    }
  };
  data.forEach((node) => {
    if (node.type === 'category') {
      indexCategory(node);
    }
  });

  const transform = (node) => ({
    ...node,
    label: `${node.value} - ${node.label}`,
    children: node.children ? node.children.map(transform) : undefined,
  })
  data = data.map(transform)

  // function onNodeSelected({ currentNode, selectedNodes }) {
  //   // const ids = getAllSelectedIds(selectedNodes);
  //   console.log('onChange::', currentNode, selectedNodes)
  //   // const courses = getAllSelectedIds(selectedNodes);
  //   // onValueChanged(courses);
  // }
// }
  const onNodeSelected = (currentNode, selectedNodes) => {
    // console.log('onChange:', currentNode, selectedNodes)
    const newSelected = getAllSelectedIds(selectedNodes,data, indexedCategories);
    onValueChanged(newSelected);
  }

  function onAction(){

  }

  function onNodeToggle(){

  }
  // console.log('Course Tree render');
  return (
    <DropdownTreeSelectHOC 
      id={id}
      data={data} 
      onChange={onNodeSelected} 
      onAction={onAction} 
      onNodeToggle={onNodeToggle}
      />
  );
})

export default Tree;
