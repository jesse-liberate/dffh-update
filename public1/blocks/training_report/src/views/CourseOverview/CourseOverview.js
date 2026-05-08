import React from 'react';
import ReactDOM from 'react-dom';
import axios from 'axios'
import Lib from '../../lib'
import { imgUrlToImgData } from '../../shared/utility';
import ResultPanel from "../../components/resultPanel";
import FilterPanel from "../../components/filterPanel";
import CourseSelector from '../../components/CourseSelector';
import CompletionSelector from '../../components/CompletionSelector';
import EnrolledDateSelector from '../../components/EnrolledDateSelector';
import CompletionDateSelector from '../../components/CompletionDateSelector';
import FromToDateSelector from '../../components/FromToDateSelector';
import HierarchySelector from '../../components/HierarchySelector';
import SuspendSelector from '../../components/SuspendSelector';
import SingleSelector from '../../components/SingleSelector';
import MultiSelector from '../../components/MultiSelector';
import Checkbox from '../../components/Checkbox';
import ExportTypeSelector from '../../components/ExportTypeSelector';
import ChartsZone from '../../components/ChartsZone';
import fileDownload from 'js-file-download'
import jsPDF from "jspdf";
import Worker from "../Worker/chartfile";
export default class CourseOverview extends React.Component{

  constructor(props){
    super(props);

    this.state = {
        tableData: [],
        courses: [],
        selectedCourseIds:[],
        completion:null,
        hierarchyNodes: [],
        hiearchyNodeSelected: '',
        suspendedFilters:[
          {
            label: 'Only',
            value: -1,
          },
          {
            label: 'Exclude',
            value: 0,
          },
          {
            label: 'Include',
            value: 1,
          },
        ],
        exportTypeFilters:[
          {
            label: 'HTML',
            value: 'HTML',
          },
          {
            label: 'Excel/CSV',
            value: 'excel'
          },
          {
            label: 'PDF',
            value: 'pdf'
          },
        ],
        enrolledDateFrom: null,
        enrolledDateTo: null,
        completionDateFrom: null,
        completionDateTo: null,
        addtionalFilters: [],
        suspended: 1,
        recordperpage: 100,
        pagenum: 1,
        total_page:'',
        display: 'HTML',
        isFirst: true,
        isLoading: false,
        isError:false,
        errorMsg: '',
        reportCode: '',
        reportHeaders:[],
        reportBody: [],
        sortStatus:[],
        courseCategory: "",
        order_by: [],
        is_hierarchy_installed:0,
        progress: null,
        overall_completed: null,
        graph_colors:{
          'bar_completed_color' : '',
          'bar_not_completed_color': '',
          'course_overview_percentage_background_color' : '',
          'course_overview_percentage_text_color' : '',
          'pie_completed_color' : '',
          'pie_completed_highlight_color' : '',
          'pie_not_completed_color' : '',
          'pie_not_completed_highlight_color' : ''
        },
        submittedDisplay:false,
        logoUrl: '',
    }
    this.courseOverviewCharts = React.createRef();
    this.onCourseSelected = this.onCourseSelected.bind(this);
    this.menuHandlers = {};
  }

  componentDidMount(){
    const date = new Date();
    // get_form_data
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_form_data',
      payload: {
        report_type:'course'
      },
    }).then(res => {
      // console.log(res.data);
      let courses = Object.values(res.data.data.courses);
        courses.forEach((childCourse, idx, parent)=>{
            this.courseDataTransform(childCourse, idx, parent);
        })
      let hierarchy = []
      if(res.data.data.hierarchy_nodes){
        hierarchy = res.data.data.hierarchy_nodes;  
        hierarchy['isDefaultValue'] = true;
      } 
      let logoUrl = ''
      if(res.data.data.logoURL){
        logoUrl = res.data.data.logoURL
      }
      this.setState({
        courses: courses,
        hierarchyNodes: hierarchy,
        hiearchyNodeSelected: hierarchy.value,
        is_hierarchy_installed:res.data.data.is_hierarchy_installed,
        logoUrl: logoUrl
      })
      
    })

      //hierarchy data prepare
    // axios.post(Lib.mock_api_path.general, {
    //   action: 'get_hierarchy_data',
    //   payload: {

    //   },
    // }).then(res => {
    //   this.setState({hierarchyNodes: res.data})
    //   // console.log(res.data)
    // })

    //config info prepare
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_config_info',
      payload: {

      },
    }).then(res => {
      console.log(res.data)
      const convertFilterObj = (filterObj) => {
        let filters = []
        Object.keys(filterObj).forEach(v => {
          filters.push({
            key: v,
            ...filterObj[v],
          })
        })
        return filters;
      };

      let profileFieldFilters = convertFilterObj(res.data.data.filter_user_profile_fields)
      let defaultFieldFilters = convertFilterObj(res.data.data.filter_user_default_fields)
      
      let filters = profileFieldFilters.concat(defaultFieldFilters)
      //set addtional state
      filters.forEach(filter=>{
        let data = {}
        if(filter.type=='datetime'){
          data[`${filter.key}_from`] = '';
          data[`${filter.key}_to`] = '';
        }else{
          data[filter.key] = '';
        }
        this.setState(data)
      })
      let graphColors = {};
      graphColors.bar_completed_color = res.data.data.bar_completed_color
      graphColors.bar_not_completed_color = res.data.data.bar_not_completed_color
      graphColors.course_overview_percentage_background_color = res.data.data.course_overview_percentage_background_color
      graphColors.course_overview_percentage_text_color = res.data.data.course_overview_percentage_text_color
      graphColors.pie_completed_color = res.data.data.pie_completed_color
      graphColors.pie_completed_highlight_color = res.data.data.pie_completed_highlight_color
      graphColors.pie_not_completed_color = res.data.data.pie_not_completed_color
      graphColors.pie_not_completed_highlight_color = res.data.data.pie_not_completed_highlight_color
      // console.log(filters);
      this.setState({addtionalFilters: filters, graph_colors: graphColors})
    })
    
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.pagenum !== this.state.pagenum||prevState.order_by !== this.state.order_by) {
      this.renderTable('update')
    }
  }

  courseDataTransform = (course, idx, parent) =>{
    if(course.children){
        course.children = Object.values(course.children)
        parent[idx] = course
        course.children.forEach((childCourse, idx, parent)=>{
            this.courseDataTransform(childCourse, idx, parent);
        })
      if(course.children.length<=0){
        course = Object.assign(course, {disabled:true});
      }
    }
  }

  onCourseSelected = (newCoursesIds) => {
    this.setState({selectedCourseIds: newCoursesIds})
  }

  onHierarchySelected = (newNode) => {
    const newNodeId = parseInt(newNode.map(node => node.value).slice().join());
    this.setState({hiearchyNodeSelected: newNodeId})
  }

  handleTextInputChange  = (filter, event) => {
    let data = {}
    data[filter.key] = event.target.value;
    this.setState(data)
  }

  saveData = (dataType, value) => {
    this.setState({[dataType]:value})
  }


  renderAddtionalFilters = (filter, index) => {
      switch (filter.type) {
          case "text":
              return (
                  <div className="tw-my-3 filter panel-menu" key={index}>
                      <div className="color-brand-1 font-weight-bold">
                          {filter.name}
                      </div>
                      <div className="filter-content">
                          <input
                              name={filter.key}
                              type={filter.type}
                              value={this.state[filter.key]}
                              onChange={this.handleTextInputChange.bind(
                                  this,
                                  filter
                              )}
                          />
                      </div>
                  </div>
              );

          case "menu":
              if (!this.menuHandlers[filter.key]) {
                  this.menuHandlers[filter.key] = (value) => {
                      this.saveData(filter.key, value);
                  };
              }
              return (
                  <div className="tw-my-3 filter panel-menu" key={index}>
                      <div className="color-brand-1 font-weight-bold">
                          {filter.name}
                      </div>
                      <div className="filter-content">
                          <MultiSelector
                              data={filter.options}
                              onValueChanged={this.menuHandlers[filter.key]}
                          />
                      </div>
                  </div>
              );

          case "multiselect":
          case "menuwithfreetext":
              if (!filter.options) {
                  return;
              }
              if (!this.menuHandlers[filter.key]) {
                  this.menuHandlers[filter.key] = (value) => {
                      this.saveData(filter.key, value);
                  };
              }
              return (
                  <div className="tw-my-3 filter panel-menu" key={index}>
                      <div className="color-brand-1 font-weight-bold">
                          {filter.name}
                      </div>
                      <div className="filter-content">
                          <MultiSelector
                              data={filter.options}
                              onValueChanged={this.menuHandlers[filter.key]}
                          />
                      </div>
                  </div>
              );

          case "datetime":
              return (
                  <div className="tw-my-3 filter panel-datepicker" key={index}>
                      <h4 className="color-brand-1 font-weight-bold">
                          {filter.name}
                      </h4>
                      {/* <DateSelector onValueChanged={value=>this.saveData('lastAccessDate', value.getTime()/1000)} /> */}
                      <FromToDateSelector
                          onFromValueChanged={(value) =>
                              value
                                  ? this.saveData(
                                        filter.key + "_from",
                                        value.getTime() / 1000
                                    )
                                  : this.saveData(filter.key + "_from", null)
                          }
                          onToValueChanged={(value) =>
                              value
                                  ? this.saveData(
                                        filter.key + "_to",
                                        value.getTime() / 1000 + 86400 - 1
                                    )
                                  : this.saveData(filter.key + "_to", null)
                          }
                      />
                  </div>
              );

          case "checkbox":
              return (
                  <div className="tw-my-3 filter panel-checkbox" key={index}>
                      <h4 className="color-brand-1 font-weight-bold">
                          {filter.name}
                      </h4>
                      <Checkbox
                          data={0}
                          onValueChanged={(value) =>
                              this.saveData(filter.key, value)
                          }
                      />
                  </div>
              );
      } 
    }

  updateReportState= (stateObj)=>{
    this.setState(stateObj)
  }
  renderSubmit(param) {
    switch(param) {
      case 'HTML':
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1 && this.state.hiearchyNodeSelected.length<=0} onClick={()=>this.renderTable('new')}>Submit</button>;
      case 'excel':
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1 && this.state.hiearchyNodeSelected.length<=0} onClick={this.exportCSV}>Download</button>;
      case 'pdf':
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1 && this.state.hiearchyNodeSelected.length<=0} onClick={this.exportPDF}>Download</button>;
      default:
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1 && this.state.hiearchyNodeSelected.length<=0} onClick={()=>this.renderTable('new')}>Submit</button>;
    }
  }

  exportPDFFile =  (reHeaders,reBody,reportTitle,filename) => {
    const unit = "pt";
    // const size = "A4"; // Use A1, A2, A3 or A4
    const size = [
      1275.59,
      595.276,
    ]; 
    const orientation = "landscape"; // portrait or landscape

    const marginLeft = 40;
    const doc = new jsPDF(orientation, unit, size);

    // doc.setFontSize(15);

    const reportBody =  reBody;
    const reportHeaders =  reHeaders;
    const title = reportTitle;
    
    let headers = []
    const header= reportHeaders.map((header)=>{
      return header.display
    })
    headers.push(header);
    let data = this.renderFormattedTableBody(reportHeaders,reportBody)
    // console.log(data)

    //
    const { pie, bar } = this.courseOverviewCharts.current || {};
    const pieCanvas = ReactDOM.findDOMNode(pie.current).querySelector('#courseoverview-pie canvas');
    const pieWidth = pieCanvas.width;
    const pieHeight = pieCanvas.height;
    const pieDataUrl = this.canvasToPng(pieCanvas);
    console.log(pieDataUrl);

    const docWidth = doc.internal.pageSize.getWidth();
    const pieDraw = {
      width: pieWidth * .80,
      height: pieHeight * .80,
      x: marginLeft,
      y: 100,
    };
    pieDraw.drawWidth = (docWidth / 2) - marginLeft - marginLeft/2;
    pieDraw.drawHeight = pieDraw.height * pieDraw.drawWidth / pieDraw.width;
    

    const barCanvas = ReactDOM.findDOMNode(bar.current).querySelector('#courseoverview-bar canvas');
    // debugger;
    // console.log(barCanvas);
    const barWidth = barCanvas.width;
    const barHeight = barCanvas.height;
    const barDataUrl = this.canvasToPng(barCanvas);
    // console.log(barWidth,barHeight,barDataUrl)
    const barDraw = {
      width: barWidth * .80,
      height: barHeight * .80, 
      x: marginLeft,
      y: 30,
    };
    barDraw.drawWidth = (docWidth / 1.2) - marginLeft - marginLeft/2;
    barDraw.drawHeight = barDraw.height * barDraw.drawWidth / barDraw.width;

    let content = {
      startY: 120,
      head: headers,
      body: data
    };
    let imgDataPromise = this.state.logoUrl ? imgUrlToImgData(this.state.logoUrl) : Promise.resolve({});
    let chartInfo = {
      imgType: 'png',
      pieDataUrl: pieDataUrl,
      pieDrawX: pieDraw.x, 
      pieDrawY: pieDraw.y,
      pieDrawWidth: pieDraw.drawWidth,
      pieDrawHeight: pieDraw.drawHeight,
      barDataUrl: barDataUrl,
      barDrawX: barDraw.x, 
      barDrawY: barDraw.y,
      barDrawWidth: barDraw.drawWidth,
      barDrawHeight: barDraw.drawHeight
    }
    const worker = new Worker();
    worker.addEventListener('message', (e) => {
      const blob = e.data;
      fileDownload(blob, filename);
      worker.terminate();
    });
    imgDataPromise.then((imgData) => {
      worker.postMessage({
        orientation,
        unit,
        size,
        title,
        marginLeft,
        content,
        imgData,
        chartInfo
      });
    });

    // doc.text(title, marginLeft, 40);
    // doc.addImage(pieDataUrl, 'png', pieDraw.x, pieDraw.y, pieDraw.drawWidth, pieDraw.drawHeight);
    // doc.addPage();
    // doc.addImage(barDataUrl, 'png', barDraw.x, barDraw.y, barDraw.drawWidth, barDraw.drawHeight);
    // doc.addPage();
    // doc.autoTable(content);
    // doc.save(filename)
  }
  canvasToPng = (canvas) => {
    return canvas.toDataURL('image/png', 1.0);
  };

  renderTable = (type) => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true,  submittedDisplay: true})
      if(type=='new'){
        this.setState({pagenum:1})
      }
      if (this.courseOverviewCharts.current && this.courseOverviewCharts.current.bar.current) {
        this.courseOverviewCharts.current.bar.current.redraw();
      }
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'course',
      payload: {
        course: this.state.selectedCourseIds,
        completion:this.state.completion,
        enrolled_from:this.state.enrolledDateFrom ? this.state.enrolledDateFrom : null ,
        enrolled_to:this.state.enrolledDateTo ? this.state.enrolledDateTo : null,
        completion_from:this.state.completionDateFrom ? this.state.completionDateFrom: null,
        completion_to:this.state.completionDateTo ? this.state.completionDateTo: null,
        country: this.state.country ? this.state.country: null,
        city: this.state.city ? this.state.city: null,
        hierarchy:this.state.hiearchyNodeSelected,
        suspended: this.state.suspended,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        graph: 1,
        display:'HTML',
        order_by: this.state.order_by,
        ...this.state.addtionalFilters.reduce((result, filter) => {
          if (filter.type === 'datetime') {
            result[`${filter.key}_from`] = this.state[`${filter.key}_from`];
            result[`${filter.key}_to`] = this.state[`${filter.key}_to`];
            return result;
          }
          result[filter.key] = this.state[filter.key];
          return result;
        }, {}),
      },
    }).then(res => {
      // console.log(res.data);
      if(res.data.error){
        this.setState({
          isLoading: false,
          isError: true,
          errorMsg: res.data.error
        })
        return
      }
      if(type=='new'){
        const newSortStatus = []
        res.data.data.headers.forEach(v => {
          newSortStatus.push({
            key:v.key,
            order:null
          })
        })
        this.setState({sortStatus: newSortStatus})
      }
      this.setState({
        isError:false,
        isLoading: false,
        reportCode: 0,
        overall_completed: res.data.data.overall_completed,
        progress: res.data.data.progress,
        reportBody: res.data.data.data,
        reportHeaders: res.data.data.headers,
        recordperpage: res.data.data.per_page,
        total_page:res.data.data.total_page,
      })
    })
    .catch(function (error) {
      // handle error
      console.log(error);
    })
  }

  exportCSV = () => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true})
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'course',
      payload: {
        course: this.state.selectedCourseIds,
        completion:this.state.completion,
        enrolled_from:this.state.enrolledDateFrom ? this.state.enrolledDateFrom : null ,
        enrolled_to:this.state.enrolledDateTo ? this.state.enrolledDateTo : null,
        completion_from:this.state.completionDateFrom ? this.state.completionDateFrom: null,
        completion_to:this.state.completionDateTo ? this.state.completionDateTo: null,
        hierarchy:this.state.hiearchyNodeSelected,
        suspended: this.state.suspended,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        display:'excel',
        order_by: this.state.order_by,
        ...this.state.addtionalFilters.reduce((result, filter) => {
          if (filter.type === 'datetime') {
            result[`${filter.key}_from`] = this.state[`${filter.key}_from`];
            result[`${filter.key}_to`] = this.state[`${filter.key}_to`];
            return result;
          }
          result[filter.key] = this.state[filter.key];
          return result;
        }, {}),
      },
    }).then(res => {
      var today = new Date();
      var dd = String(today.getDate()).padStart(2, '0');
      var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
      var yyyy = today.getFullYear();
      
      today = dd + '-' + mm + '-' + yyyy;
      var filename = 'sgl_and_training_module-' + today + '.csv';
      fileDownload(res.data, filename);
      this.setState({isLoading: false})
    })
    .catch(function (error) {
      // handle error
      console.log(error);
      // this.setState({
      //   isLoading: false
      // })
    })
  }


  exportPDF = () => {
    this.setState({isFirst:false, isLoading: true, pagenum:1, submittedDisplay: true})
    axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
    action: 'course',
    payload: {
      course: this.state.selectedCourseIds,
      completion:this.state.completion,
      enrolled_from:this.state.enrolledDateFrom ? this.state.enrolledDateFrom : null ,
      enrolled_to:this.state.enrolledDateTo ? this.state.enrolledDateTo : null,
      completion_from:this.state.completionDateFrom ? this.state.completionDateFrom: null,
      completion_to:this.state.completionDateTo ? this.state.completionDateTo: null,
      hierarchy:this.state.hiearchyNodeSelected,
      suspended: this.state.suspended,
      graph: 1,
      recordperpage: this.state.recordperpage,
      pagenum:this.state.pagenum,
      display:'pdf',
      order_by: this.state.order_by,
      ...this.state.addtionalFilters.reduce((result, filter) => {
        if (filter.type === 'datetime') {
          result[`${filter.key}_from`] = this.state[`${filter.key}_from`];
          result[`${filter.key}_to`] = this.state[`${filter.key}_to`];
          return result;
        }
        result[filter.key] = this.state[filter.key];
        return result;
      }, {}),
    },
    }).then(res => {
      // console.log(res.data)
      if(res.data.error){
        this.setState({
          isLoading: false,
          isError: true,
          errorMsg: res.data.error
        })
        return
      }
      const newSortStatus = []
        res.data.data.headers.forEach(v => {
          newSortStatus.push({
            key:v.key,
            order:null
          })
        })
      this.setState({
        sortStatus: newSortStatus,
        isError:false,
        // isLoading: false,
        reportCode: 0,
        overall_completed: res.data.data.overall_completed,
        progress: res.data.data.progress,
        reportBody: res.data.data.data,
        reportHeaders: res.data.data.headers,
        recordperpage: res.data.data.per_page,
        total_page:res.data.data.total_page,
      })
    
    this.setState({isLoading: false})
    setTimeout(() => {
      this.exportPDFFile(res.data.data.headers,res.data.data.data,"SGL & Training Module Report","SGL & Training Module Report.pdf")
    }, 0);
    })
    .catch(function (error) {
    // handle error
    console.log(error);
    // this.setState({
    //   isLoading: false
    // })
    })

  }

  renderFormattedTableBody(headers, body){
    let newBody = []
    body.map((data, index)=>{
      let newRow = [];
      headers.map((header,i)=>{
        let key = header.key
        if(key in data){
          newRow.push(data[key])
        }
      })
      newBody.push(newRow);
    })
    return newBody;
  }


  render() {
    const { courses, completionFilters, suspendedFilters, hierarchyNodes, addtionalFilters, reportCode, reportHeaders, reportBody, isLoading, isFirst, sortStatus, recordperpage, total_page, pagenum, order_by, exportTypeFilters, display, submittedDisplay, isError, errorMsg, overall_completed, progress, graph_colors, courseCategory } = this.state;
    return (
        <div className="row">
          <div className="filter-zone col-lg-3">
              <div className="w-100 tw-p-3">
                  <FilterPanel>
                    <CourseSelector courses={courses} onValueChanged={this.onCourseSelected} />  
                    {/* <EnrolledDateSelector 
                      onEnrolledFromValueChanged={value=>(value ? this.saveData('enrolledDateFrom', value.getTime()/1000): this.saveData('enrolledDateFrom', null))} 
                      onEnrolledToValueChanged={value=>(value ? this.saveData('enrolledDateTo', value.getTime()/1000+86400-1): this.saveData('enrolledDateTo',null))}
                    /> */}
                    <CompletionDateSelector 
                      onCompletionFromValueChanged={value=>(value ? this.saveData('completionDateFrom', value.getTime()/1000): this.saveData('completionDateFrom', null))} 
                      onCompletionToValueChanged={value=>(value ? this.saveData('completionDateTo', value.getTime()/1000+86400-1):this.saveData('completionDateTo',null))}
                    />
                    {
                      (hierarchyNodes.children && hierarchyNodes.children.length > 0) || hierarchyNodes.id ? 
                      <HierarchySelector nodes={hierarchyNodes} onValueChanged={this.onHierarchySelected} />  
                      :
                      ''
                    } 
                    {addtionalFilters.map((filter,index) => 
                      this.renderAddtionalFilters(filter, index)
                    )}
                    <SuspendSelector suspended={suspendedFilters} onValueChanged={value=>this.saveData('suspended', value.value)} select={{label: 'Exclude',value: 0}} />
                    <ExportTypeSelector suspended={exportTypeFilters} onValueChanged={value=>this.saveData('display', value.value)} select={{label: 'HTML', value: 'HTML'}} />
                    {isLoading ? 'Loading...':''}
                  </FilterPanel>
                  <div className="operate-zone btns tw-my-3 clearfix">
                    <a className="btn btn-primary tw-float-left" href={M.cfg.wwwroot+'/blocks/training_report'}>Back</a>
                    {this.renderSubmit(display)}
                  </div>
                </div>
              </div>

          <div className="report-zone col-lg-9 tw-bg-gray-100 tw-box-border">
              <div className="tw-h-full tw-w-full tw-px-3">
                {
                  overall_completed !=null && progress != null ?
                  <ChartsZone 
                  ref={this.courseOverviewCharts} overallCompleted={overall_completed} progress={progress} 
                  graphColors={graph_colors}
                  />
                  :
                  ''
                }
                  <ResultPanel 
                    isError={isError}
                    errorMsg={errorMsg}
                    reportBody={reportBody}
                    reportHeaders={reportHeaders}
                    isLoading={isLoading}
                    isFirst={isFirst}
                    sortStatus={sortStatus}
                    total_page={total_page}
                    pagenum={pagenum}
                    order_by={order_by}
                    submittedDisplay={submittedDisplay}
                    updateReportState={this.updateReportState}>
                  </ResultPanel>
              </div> 
          </div>
        </div>

    );
  }
}