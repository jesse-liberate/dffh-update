import React,{Component} from 'react';
import {Bar} from 'react-chartjs-2';



// export default class CourseBarChart extends PurePureComponent
// export default React.createClass({
export default class CourseBarChart extends Component{

  constructor(props) {
    super(props);
    this.shouldRedraw = false;
  }

  redraw() {
    this.shouldRedraw = true;
  }


  render() {
    const {progress, graphColors} = this.props
    const { shouldRedraw } = this;
    this.shouldRedraw = false;
    let courseLabels = [];
    let courseData = [];
    progress.forEach(item=>{
      courseLabels.push(item.name)
      courseData.push(item.number*100)
    })


    const data = {
      labels: courseLabels,
      datasets: [
        {
          label: '% of users who completed',
          backgroundColor:graphColors.bar_completed_color,
          borderColor:graphColors.bar_completed_color,
          hoverBackgroundColor:graphColors.bar_completed_color,
          hoverBorderColor:graphColors.bar_completed_color,
          borderWidth: 1,
          data: courseData
        }
      ]
    };
    const options = {
      title: {
          display: true,
          text: 'Course Progress',
          position: 'top',
      },
      scales: {
        yAxes: [{
            scaleLabel: {
                display: true,
                labelString: '% of users who completed',
            },
            ticks: {
                max: 100,
                min: 0,
                stepSize: 10 
            }
        }]
      },
      legend: {
        display: false,
      },
      tooltips: {
        enabled: true,
        callbacks: {
            label: function(tooltipItem, data) {
                var label = data.datasets[tooltipItem.datasetIndex].label || '';

                if (label) {
                    label += ': ';
                }
                label += Math.round(tooltipItem.yLabel * 100) / 100;
                return label;
            }
        }
      }
    }
    return (
      <div >
        <Bar
          data={data}
          redraw={shouldRedraw}
          options={options}
        />
      </div>
    );
  }
};