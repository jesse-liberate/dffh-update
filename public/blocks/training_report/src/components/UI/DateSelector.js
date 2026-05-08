import React, { useState } from "react";
import DatePicker from "react-datepicker";

// import "react-datepicker/dist/react-datepicker.css";
/*
react-datepicker
https://www.npmjs.com/package/react-datepicker
*/

// CSS Modules, react-datepicker-cssmodules.css
// import 'react-datepicker/dist/react-datepicker-cssmodules.css';

const DateSelector = ({date, onValueChanged, minDate}) => {
  const [startDate, setStartDate] = useState(date);
  // const date = new Date()
  const handleDateChange = (date) => {
    setStartDate(date)
    onValueChanged(date)
  }

  return (
    // <DatePicker selected={date} onChange={handleDateChange} />
    <DatePicker selected={startDate} onChange={handleDateChange} dateFormat="dd/MM/yyyy" minDate={minDate}/>
  );
};

export default DateSelector;