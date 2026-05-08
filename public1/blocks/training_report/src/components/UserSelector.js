import React from 'react';
import SingleDropdownSearch from './UI/SingleDropdownSearch';

const UserSelector = ({ users, onValueChanged, select }) => {
  console.log('select' + select)
  return (
    <div className="tw-my-3 filter users-filter">
      <div className="color-brand-1 font-weight-bold">User</div>
      <div className="filter-content">
        <SingleDropdownSearch 
          data={users} 
          onValueChanged={onValueChanged}
          searchString={["Search for user", "No matching user"]}
          select={select}
          />        
      </div>
    </div>
  );
}

export default UserSelector;
