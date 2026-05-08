import React, { useState }  from 'react';
import DateSelector from './UI/DateSelector';

const FromToDateSelector = ({onFromValueChanged, onToValueChanged }) => {

  // const From = new Date();
  // const To = new Date();

  const [fromDate, setFromDate] = useState(0);

  const onFromChanged = (date) => {
    setFromDate(date)
    onFromValueChanged(date)
  }

  return (
    <div className="tw-my-3 filter -date-selector">
      <div className="filter-content tw-grid tw-grid-cols-2">
        <div className="date-selector-row">
          <span className="date-selector-label tw-block">From</span>
          <DateSelector 
            onValueChanged={onFromChanged}/>          
        </div>

        <div className="date-selector-row">
          <span className="date-selector-label tw-block">To</span>
          <DateSelector 
            onValueChanged={onToValueChanged}
            minDate={fromDate} />
        </div>

      </div> 
    </div>
  );
}

export default FromToDateSelector;
