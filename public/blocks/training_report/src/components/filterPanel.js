import React from 'react';

const FilterPanel = (props) => {
  return (
    <div className="filterPanel">
      {props.children}
    </div>
  );
}

export default FilterPanel;
