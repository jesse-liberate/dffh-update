import React, { useState }  from 'react';
import DateSelector from './UI/DateSelector';

const EnrolledDateSelector = ({onEnrolledFromValueChanged, onEnrolledToValueChanged }) => {

  // const enrolledFrom = new Date();
  // const enrolledTo = new Date();
  const [fromDate, setFromDate] = useState(0);

  const onFromValueChanged = (date) => {
    setFromDate(date)
    onEnrolledFromValueChanged(date)
  }
  
  return (
    <div className="tw-my-3 filter enrolled-date-selector">
      <div className="color-brand-1 font-weight-bold">Enrolled Date</div>
      <div className="filter-content tw-grid tw-grid-cols-2">
        <div className="date-selector-row">
          <span className="date-selector-label tw-block">From</span>
          <DateSelector
            onValueChanged={onFromValueChanged}/>          
        </div>

        <div className="date-selector-row">
          <span className="date-selector-label tw-block">To</span>
          <DateSelector 
            onValueChanged={onEnrolledToValueChanged}
            minDate={fromDate}/>
        </div>

      </div> 
    </div>
  );
}

export default EnrolledDateSelector;
