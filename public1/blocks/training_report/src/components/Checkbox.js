import React, { useState }  from 'react';


const Checkbox = ({data, onValueChanged}) => {
  const [checked, setchecked] = useState(data);

  const handleCheck = (event) => {
    console.log(event.target.checked)
    setchecked(event.target.checked)
    onValueChanged(event.target.checked ? 1:0)
  }

  return (
    <input type="checkbox"  checked={checked} onChange={handleCheck} />
  );
};

export default Checkbox;

