import React,{Component} from 'react';
import {Pie} from 'react-chartjs-2';

// const data = {
// 	labels: [
// 		'Red',
// 		'Blue',
// 		'Yellow'
// 	],
// 	datasets: [{
// 		data: [300, 50, 100],
// 		backgroundColor: [
// 		'#FF6384',
// 		'#36A2EB',
// 		'#FFCE56'
// 		],
// 		hoverBackgroundColor: [
// 		'#FF6384',
// 		'#36A2EB',
// 		'#FFCE56'
// 		]
// 	}]
// };

// export default React.createClass({
export default class CoursePieChart extends Component{
  // displayName: 'PieExample',
  drawSegmentValues()
  {
      for(var i=0; i<myPieChart.segments.length; i++) 
      {
          ctx.fillStyle="white";
          var textSize = canvas.width/10;
          ctx.font= textSize+"px Verdana";
          // Get needed variables
          var value = myPieChart.segments[i].value;
          var startAngle = myPieChart.segments[i].startAngle;
          var endAngle = myPieChart.segments[i].endAngle;
          var middleAngle = startAngle + ((endAngle - startAngle)/2);

          // Compute text location
          var posX = (radius/2) * Math.cos(middleAngle) + midX;
          var posY = (radius/2) * Math.sin(middleAngle) + midY;

          // Text offside by middle
          var w_offset = ctx.measureText(value).width/2;
          var h_offset = textSize/4;

          ctx.fillText(value, posX - w_offset, posY + h_offset);
      }
  }

  render() {
    const {overallCompleted, graphColors} = this.props
    const data = {
      labels: [
        'Completed',
        'Not Completed',
      ],
      datasets: [{
        data: [overallCompleted, 1 - overallCompleted],
        backgroundColor: [
        graphColors.pie_completed_color,
        graphColors.pie_not_completed_color
        // '#FF6384',
        // '#36A2EB',
        ],
        // hoverBackgroundColor: [
        // graphColors.pie_completed_color,
        // graphColors.pie_not_completed_color
        // // '#FF6384',
        // // '#36A2EB',
        // ]
      }]
    };

    const options = {
        title: {
            display: true,
            text: 'Overall Progress',
            position: 'top',
        },
        tooltips: {
          enabled: false
        },
        hover: {
          mode: null
        },
        events: [
          
        ],
        animation: {
          duration: 500,
          easing: "easeOutQuart",
          onComplete: function () {
            console.log(this);
            var ctx = this.chart.ctx;
            ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
      
            this.data.datasets.forEach(function (dataset) {
      
              for (var i = 0; i < dataset.data.length; i++) {
                var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
                    total = dataset._meta[Object.keys(dataset._meta)[0]].total,
                    mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2,
                    start_angle = model.startAngle,
                    end_angle = model.endAngle,
                    mid_angle = start_angle + (end_angle - start_angle)/2;
      
                var x = mid_radius * Math.cos(mid_angle);
                var y = mid_radius * Math.sin(mid_angle);
      
                ctx.fillStyle = '#fff';
                if (i == 3){ // Darker text color for lighter background
                  ctx.fillStyle = '#444';
                }
      
                var val = dataset.data[i];
                var percent = String(Math.round(val/total*100)) + "%";
      
                if(val != 0) {
                  // ctx.fillText(dataset.data[i], model.x + x, model.y + y);
                  // Display percent in another line, line break doesn't work for fillText
                  ctx.fillText(percent, model.x + x, model.y + y + 15);
                }
              }
            });               
          }
        }
      }
    return (
      <div>
        {/* <h2 className="tw-text-center">Overall Progress</h2> */}
        <Pie 
        data={data}
        options={options}
        />
      </div>
    );
  }
};