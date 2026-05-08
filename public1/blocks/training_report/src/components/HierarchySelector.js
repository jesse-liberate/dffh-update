import React from 'react';
import SingleTree from './UI/SingleTree';

const HierarchySelector = ({ nodes, onValueChanged}) => {
  return (
    <div className="tw-my-3 filter hierarchy-filter">
      <div className="color-brand-1 font-weight-bold">Hierarchy <span className="tw-text-red-500">*</span></div>
      <div className="filter-content">
        <SingleTree 
          data={nodes} 
          onValueChanged={onValueChanged}
          />    
      </div>
    </div>
  );
}

export default HierarchySelector;

