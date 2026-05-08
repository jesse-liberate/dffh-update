import React, { Component } from 'react';
import { PieChart, Pie, Sector,Tooltip, XAxis, Label, Legend, Cell, ResponsiveContainer } from 'recharts';

// const data = [
//   { name: 'Group A', value: 400 },
//   { name: 'Group B', value: 300 },
//   // { name: 'Group C', value: 300 },
//   // { name: 'Group D', value: 200 },
// ];

// const colors = ['#0088FE', '#00C49F'];

const RADIAN = Math.PI / 180;
const renderCustomizedLabel = ({ cx, cy, midAngle, innerRadius, outerRadius, percent, index }) => {
  const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
  const x = cx + radius * Math.cos(-midAngle * RADIAN);
  const y = cy + radius * Math.sin(-midAngle * RADIAN);

  return (
    <text x={x} y={y} fill="white" textAnchor={x > cx ? 'start' : 'end'} dominantBaseline="central">
      {`${(percent * 100).toFixed(0)}%`}
    </text>
  );
};

export default class CoursePieChart extends Component {
  // // static demoUrl = 'https://codesandbox.io/s/pie-chart-with-customized-label-dlhhj';
  // constructor(props){
  //   super(props);
  // }
  // state = {
  //   overallCompleted: this.props.overallCompleted
  // }
  // // componentDidMount(){
  // //   this.data = [
  // //     { name: 'Completed', value: 1000 * this.props.overall_completed },
  // //     { name: 'Not completed', value: 1000 - 1000 * this.props.overall_completed },
  // //   ];
  // // }
  renderLabel = () =>{
    retrun (

    )
  }

  render() {
    const {overallCompleted, graphColors} = this.props

   
    // const data = [
    //   { name: 'Completed', value: 1000 * parseFloat(overallCompleted)} ,
    //   { name: 'Not completed', value: 1000 - 1000*parseFloat(overallCompleted)},
    // ];

    const data = [
      { name: 'Completed', value: overallCompleted} ,
      { name: 'Not completed', value: 1-overallCompleted},
    ];

    const colors = [
      graphColors.pie_completed_color,
      graphColors.pie_not_completed_color
    ];

    console.log(graphColors)
    return (
      <ResponsiveContainer width="100%" height="100%">
        <PieChart width={this.props.pieWidth} height={this.props.pieHeight}>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            labelLine={false}
            label={renderCustomizedLabel}
            // label={renderLabel}
            outerRadius={80}
            fill="#8884d8"
            // fill={graph_colors['bar_not_completed_color']}
            dataKey="value"
          >
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
            ))}
          </Pie>
          {/* <XAxis dataKey="name">
            
          </XAxis> */}
          {/* <Tooltip /> */}
          {/* <Legend verticalAlign="top" height={36}/> */}
          <Legend />
        </PieChart>
      </ResponsiveContainer>
    );
  }
}
