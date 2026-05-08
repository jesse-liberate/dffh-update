import React, { Component } from 'react'
import PieChart from './UI/CourseOverviewPie';
import BarChart from './UI/CourseOverviewBar';

export default class ChartsZone extends Component {

  constructor(props){
    super(props);
    // this.state = { 
    //   barChartWidth: 'tw-w-full' 
    // };
    this.pie = React.createRef();
    this.bar = React.createRef();
    }

    shouldComponentUpdate(nextProps, nextState){
      return this.props.progress != nextProps.progress
  }

    render(){
      return (
        <div className="courseoverview-charts-zone tw-flex tw-flex-wrap tw-mb-5 tw-mt-1" refs="">
          <div className="tw-w-1/2 tw-pb-5 tw-d-inline-block" id="courseoverview-pie">
            <PieChart ref={this.pie} {...this.props}/>
          </div>
          <div className={`tw-pb-5 tw-inline-block ${this.props.progress.length >= 10 ? 'tw-w-full' : 'tw-w-1/2'}`} id="courseoverview-bar">
            <BarChart ref={this.bar} {...this.props}/>
          </div>
        
        </div>
      )   
    }
}