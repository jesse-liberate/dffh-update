import React, { useState }  from 'react';
import DateSelector from './UI/DateSelector';

const CompletionDateSelector = ({onCompletionFromValueChanged, onCompletionToValueChanged }) => {

  // const completionFrom = new Date();
  // const completionTo = new Date();
  const [fromDate, setFromDate] = useState(0);

  const onFromValueChanged = (date) => {
    setFromDate(date)
    onCompletionFromValueChanged(date)
  }

  return (
    <div className="filter pt-3 completion-date-selector">
      <div className="color-brand-1 font-weight-bold">Completion Date</div>
      <div className="filter-content tw-grid tw-grid-cols-2">
        <div className="date-selector-row">
          <span className="date-selector-label tw-block">From</span>
          <DateSelector 
            onValueChanged={onFromValueChanged} />
        </div>
        <div className="date-selector-row">
          <span className="date-selector-label tw-block">To</span>
          <DateSelector 
            onValueChanged={onCompletionToValueChanged}
            minDate={fromDate} />        
        </div>
      </div>


    </div>
  );
}

export default CompletionDateSelector;
