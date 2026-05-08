import React from 'react';
import axios from 'axios'
import Lib from '../../lib'
import { imgUrlToImgData, updateObject } from '../../shared/utility';
import DateSelector from '../../components/UI/DateSelector';
import ResultPanel from "../../components/resultPanel";
import FilterPanel from "../../components/filterPanel";
import CourseSelector from '../../components/CourseSelector';
import CompletionSelector from '../../components/CompletionSelector';
import EnrolledDateSelector from '../../components/EnrolledDateSelector';
import CompletionDateSelector from '../../components/CompletionDateSelector';
import FromToDateSelector from '../../components/FromToDateSelector';
import HierarchySelector from '../../components/HierarchySelector';
import SuspendSelector from '../../components/SuspendSelector';
import Checkbox from '../../components/Checkbox';
import ExportTypeSelector from '../../components/ExportTypeSelector';
import SingleSelector from '../../components/SingleSelector';
import MultiSelector from '../../components/MultiSelector';
import fileDownload from 'js-file-download'
import jsPDF from "jspdf";
import Worker from "../Worker/file.worker";
export default class General extends React.Component{

  constructor(props){
    super(props);

    this.state = {
        tableData: [],
        courses: [],
        selectedCourseIds:[],
        completion:null,
        hierarchyNodes: [],
        hiearchyNodeSelected: '',
        completionFilters: [
          {
            label: 'All',
            value: 0,
          },
          {
            label: 'Not Completed',
            value: 1,
          },
          {
            label: 'Completed',
            value: 2,
          },
        ],
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
        total_page:'',
        pagenum: 1,
        display: 'HTML',
        isFirst: true,
        isLoading: false,
        isError:false,
        errorMsg: '',
        reportCode: '',
        is_hierarchy_installed:0,
        reportHeaders:[],
        reportBody: [],
        sortStatus:[],
        order_by: [],
        submittedDisplay:false,
        logoUrl: '',
    }
    this.onCourseSelected = this.onCourseSelected.bind(this);
    this.menuHandlers = {};
  }

  componentDidMount(){
    const date = new Date();
    // get_form_data
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_form_data',
      payload: {
        report_type:'general'
      },
    }).then(res => {
      // console.log(res.data)
      let courses = Object.values(res.data.data.courses);
        courses.forEach((childCourse, idx, parent)=>{
            this.courseDataTransform(childCourse, idx, parent);
        })
      let hierarchy = []
      if(res.data.data.hierarchy_nodes){
        hierarchy = res.data.data.hierarchy_nodes
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

    //config info prepare
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_config_info',
      payload: {

      },
    }).then(res => {
      // console.log(res.data);
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
        // console.log('filters'+filter);
        let data = {}
        if(filter.type=='datetime'){
          data[`${filter.key}_from`] = '';
          data[`${filter.key}_to`] = '';
        }else{
          data[filter.key] = '';
        }
        this.setState(data)
      })
      // console.log(filters);
      this.setState({addtionalFilters: filters})
    })
    
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.pagenum !== this.state.pagenum || prevState.order_by !== this.state.order_by) {
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

  //function's currying
  // saveData = (dataType) => {
  //   return (result)=>{
  //     this.setState({[dataType]: result})
  //   }
  // }

  // without currying
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
              if (!filter.options)     {
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
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1  && this.state.hiearchyNodeSelected.length<=0} onClick={()=>this.renderTable('new')}>Submit</button>;
      case 'excel':
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1  && this.state.hiearchyNodeSelected.length<=0} onClick={this.exportCSV}>Download</button>;
      case 'pdf':
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1  && this.state.hiearchyNodeSelected.length<=0} onClick={this.exportPDF}>Download</button>;
      default:
        return <button className="btn btn-primary tw-float-right" disabled={this.state.is_hierarchy_installed == 1  && this.state.hiearchyNodeSelected.length<=0} onClick={()=>this.renderTable('new')}>Submit</button>;
    }
  }

  exportPDFFile = (reHeaders,reBody,reportTitle,filename) => {
    const unit = "pt";
    const size = [
      1275.59,
      595.276,
    ]; // Use A1, A2, A3 or A4
    const orientation = "landscape"; // portrait or landscape

    const marginLeft = 40;
    // const doc = new jsPDF(orientation, unit, size);
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
    let content = {
      startY: 120,
      head: headers,
      body: data,
    };
    let imgDataPromise = this.state.logoUrl ? imgUrlToImgData(this.state.logoUrl) : Promise.resolve({});
    //
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
      });
    });

    // doc.text(title, marginLeft, 40);
    // doc.autoTable(content);
    // doc.save(filename)
  }

  renderTable = (type) => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true, submittedDisplay: true})
      if(type=='new'){
        this.setState({pagenum:1})
      }
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'general',
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
        this.setState({ sortStatus: newSortStatus})
      }
      
      this.setState({
        isError:false,
        isLoading: false,
        reportCode: 0,
        reportBody: res.data.data.data,
        reportHeaders: res.data.data.headers,
        recordperpage: res.data.data.per_page,
        total_page:res.data.data.total_page,
        isRendered: true
      })
    })
    .catch(function (error) {
      // handle error
      console.log(error);
      // this.setState({
      //   isLoading: false
      // })
    })
  }
  

  exportCSV = () => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true})
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'general',
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
        suspended: this.state.suspended,
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
      var filename = 'general-' + today + '.csv';
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
    //send current selected data to BE, get data back
    this.setState({isFirst:false, isLoading: true})
    axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
    action: 'general',
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
      if(res.data.error){
        this.setState({
          isLoading: false,
          isError: true,
          errorMsg: res.data.error
        })
        return
      }
      this.exportPDFFile(res.data.data.headers,res.data.data.data,"General Report","General Report.pdf");
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
    const {courses,completionFilters, suspendedFilters,exportTypeFilters, hierarchyNodes,
      addtionalFilters,reportCode,reportHeaders,reportBody,isLoading,isFirst,
      sortStatus,recordperpage,total_page,pagenum,display,order_by,submittedDisplay,
      isError,errorMsg,enrolledDateFrom} = this.state
    return (
        <div className="row">
          <div className="filter-zone col-lg-3">
              <div className="w-100 tw-p-3">
                  <FilterPanel>
                    <CourseSelector courses={courses} onValueChanged={this.onCourseSelected} /> 
                    <CompletionSelector completion={completionFilters} onValueChanged={value=>this.saveData('completion', value.value)} />
                    {/* <EnrolledDateSelector 
                      onEnrolledFromValueChanged={value=>(value ? this.saveData('enrolledDateFrom', value.getTime()/1000): this.saveData('enrolledDateFrom', null))} 
                      onEnrolledToValueChanged={value=>(value ? this.saveData('enrolledDateTo', (value.getTime()/1000 + 86400-1)): this.saveData('enrolledDateTo',null))}
                    /> */}
                    <CompletionDateSelector 
                      onCompletionFromValueChanged={value=>(value ? this.saveData('completionDateFrom', value.getTime()/1000): this.saveData('completionDateFrom', null))} 
                      onCompletionToValueChanged={value=>(value ? this.saveData('completionDateTo', (value.getTime()/1000 + 86400-1)):this.saveData('completionDateTo',null))}
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
                    <ExportTypeSelector  suspended={exportTypeFilters} onValueChanged={value=>this.saveData('display', value.value)} select={{label: 'HTML', value: 'HTML'}} />
                    {isLoading ? 'Loading...':''}
                  </FilterPanel>
                  <div className="operate-zone btns tw-my-3 clearfix">
                      <a className="btn btn-primary tw-float-left" href={M.cfg.wwwroot+'/blocks/training_report'}>Back</a>
                      {this.renderSubmit(display)}
                  </div>
                </div>
              </div>

          <div className="report-zone col-lg-9 tw-box-border">
              <div className="tw-h-full tw-w-full tw-px-3">
                  <ResultPanel
                    isError={isError}
                    errorMsg={errorMsg}
                    reportBody={reportBody}
                    reportHeaders={reportHeaders}
                    isLoading={isLoading}
                    isFirst={isFirst}
                    sortStatus={sortStatus}
                    recordperpage={recordperpage}
                    total_page={total_page}
                    pagenum={pagenum}
                    display={display}
                    order_by={order_by}
                    submittedDisplay={submittedDisplay}
                    updateReportState={this.updateReportState}>
                  </ResultPanel>
                  
              </div> 
              <div>
              </div>
          </div>
        </div>

    );
  }

}