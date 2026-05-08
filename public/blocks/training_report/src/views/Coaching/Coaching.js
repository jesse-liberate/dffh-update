import React from 'react';
import axios from 'axios'
import { imgUrlToImgData } from '../../shared/utility';
import ResultPanel from "../../components/resultPanel";
import FilterPanel from "../../components/filterPanel";
import MultiUserSelector from '../../components/MultiUserSelector';
import ExportTypeSelector from '../../components/ExportTypeSelector';
import { DropdownMultiple} from 'reactjs-dropdown-component';
import HierarchySelector from '../../components/HierarchySelector';
import SingleSelector from '../../components/SingleSelector';
import MultiSelector from '../../components/MultiSelector';
import FromToDateSelector from '../../components/FromToDateSelector';
import Checkbox from '../../components/Checkbox';
import fileDownload from 'js-file-download'
import jsPDF from "jspdf";
import Worker from "../Worker/file.worker";
export default class Coaching extends React.Component{

  constructor(props){
    super(props);

    this.state = {
      
      hierarchyNodes: [],
      hiearchyNodeSelected: '',
      lastAccessDate: null,
      addtionalFilters: [],
      suspended: 1,
      is_hierarchy_installed:0,
      order_by: [],
      submittedDisplay:false,
        tableData: [],
        users:[],
        forms:[],
        selectedForm: null,
        selectedValues: [],
        selectedUserId:'',
        recordperpage: 100,
        total_page:'',
        pagenum: 1,
        order_by: [],
        display: 'HTML',
        isFirst: true,
        isLoading: false,
        isError:false,
        errorMsg: '',
        sortStatus:[],
        reportCode: '',
        reportHeaders:[],
        reportBody: [],
        exportTypeFilters:[
          {
            label: 'Excel/CSV',
            value: 'excel',
          }

        ],
        submittedDisplay: false,
        logoUrl: '',
    }
    this.menuHandlers = {};
    this.addItem = this.addItem.bind(this);
  }
  addItem(value, label) {
    console.log(value);
     this.setState(prevState => ({
      selectedValues: [...prevState.selectedValues,{ value: value, label: label }]
    }))
  }
  

  componentDidMount(){
    // get_form_data
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_form_data',
      payload: {
        report_type:'coaching'
      },
    }).then(res => {

      let userId = M.cfg.userid
      // console.log(res.data)
      // console.log(res.data.data.users.filter(function (user) { return user.id == userId }))
      if(res.data.data.users.filter(function (user) { return user.id == userId })){
        this.setState({
          selectedUserId: userId
        })
      }
      let logoUrl = ''
      if(res.data.data.logoURL){
        logoUrl = res.data.data.logoURL
      }
      this.setState({
        users: res.data.data.users,
        forms: res.data.data.forms,
        logoUrl: logoUrl
      })
       // axios.post(Lib.mock_api_path.general, {
      axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
        action: 'get_config_info',
        payload: {

        },
      }).then(res => {
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
        // console.log(filters);
        console.log(filters);
        this.setState({addtionalFilters: filters})
      })
    })
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.pagenum !== this.state.pagenum || prevState.order_by !== this.state.order_by) {
      this.renderTable('update')
    }
  }
  

  saveData = (dataType, value) => {
    this.setState({[dataType]:value})
  }
  renderSubmit(param) {
    switch(param) {
      case 'excel':
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedValues} onClick={this.exportCSV}>Download</button>;
      default:
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedValues} onClick={()=>this.renderTable('new')}>Submit</button>;
    }
  }

  exportPDFFile = (reHeaders,reBody,reportTitle,filename) => {
    const unit = "pt";
    const size = [
      1275.59,
      595.276,
    ];// Use A1, A2, A3 or A4
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
      body: data
    };
    let imgDataPromise = this.state.logoUrl ? imgUrlToImgData(this.state.logoUrl) : Promise.resolve({});
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
        imgData
      });
    });

    // doc.text(title, marginLeft, 40);
    // doc.autoTable(content);
    // doc.save(filename)
  }
  
  onChange = (item, name) => {  
    this.setState(prevState => ({
    selectedValues: item
  })) }

  onChangeForm = (e, id) => {
    const { name, value } = e.target;
    this.setState(prevState => ({
      selectedForm: value
    })) 
  };

  onHierarchySelected = (newNode) => {
    const newNodeId = parseInt(newNode.map(node => node.value).slice().join());
    this.setState({hiearchyNodeSelected: newNodeId})
  }
  handleTextInputChange  = (filter, event) => {
    let data = {}
    data[filter.key] = event.target.value;
    this.setState(data)
  }
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


  renderTable = (type) => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true, submittedDisplay: true})
      if(type=='new'){
        this.setState({pagenum:1})
      }
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'coaching',
      payload: {
        user_id: this.state.selectedUserId,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        suspended: this.state.suspended,
        display:'HTML',
        order_by: this.state.order_by
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
        this.setState({sortStatus: newSortStatus})
      }
      

      this.setState({
        isError:false,
        isLoading: false,
        reportCode: 0,
        reportBody: res.data.data.data,
        reportHeaders: res.data.data.headers,
        recordperpage: res.data.data.per_page,
        total_page:res.data.data.total_page,
      })
    })
    .catch(function (error) {
      // handle error
      console.log(error);
      this.setState({
        isLoading: false
      })
    })

       // axios.post(Lib.mock_api_path.general, {


  }

  exportCSV = () => {
    //send current selected data to BE, get data back
    let formid = 0;
    if(!this.state.selectedForm){
      formid = this.state.forms[0].value
    }else{
      formid = this.state.selectedForm;
    }
      this.setState({isFirst:false, isLoading: true})
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'coaching',
      payload: {
        user_id: this.state.selectedValues,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        formid: formid,
        suspended: this.state.suspended,
        display:'excel',
        ...this.state.addtionalFilters.reduce((result, filter) => {
          if (filter.type === 'datetime') {
            result[`${filter.key}_from`] = this.state[`${filter.key}_from`];
            result[`${filter.key}_to`] = this.state[`${filter.key}_to`];
            return result;
          }
          result[filter.key] = this.state[filter.key];
          return result;
        }, {}),
        order_by: this.state.order_by

      },
    }).then(res => {
      fileDownload(res.data, 'coaching.csv');
      this.setState({isLoading: false})
    })
    .catch(function (error) {
      // handle error
      console.log(error);
      this.setState({
        isLoading: false
      })
    })

  }

  updateReportState= (stateObj)=>{
    this.setState(stateObj)
  }

  exportPDF = () => {
    this.setState({isFirst:false, isLoading: true})
    axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'coaching',
      payload: {
        user_id: this.state.selectedValues,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        suspended: this.state.suspended,
        display:'pdf',
        order_by: this.state.order_by
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
      this.exportPDFFile(res.data.data.headers,res.data.data.data,"Coaching Report","Coaching Report.pdf");
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
    const {users,selectedValues,forms,addtionalFilters,reportHeaders,reportBody,isLoading,isFirst,erroMsg,
      recordperpage,total_page,pagenum,exportTypeFilters, display, order_by,
      sortStatus,submittedDisplay,isError,errorMsg} = this.state
    
    const selectedUser = users.find((user) => user.value == M.cfg.userid);
    return (
      <div className="row">
      <div className="filter-zone col-lg-3">
          <div className="w-100 tw-p-3">
              <FilterPanel>
              <div className="tw-my-3 filter courses-filter">
              <div className="color-brand-1 font-weight-bold"> Users <span className="tw-text-red-500">*</span></div>
              <div className="filter-content">
                <DropdownMultiple 
                  list={users} 
                  onChange={this.onChange}
                  searchString={["Search for users", "No matching user"]}
                  title="Users"
                  titleSingular="User"
                  />
                    <div className="color-brand-1 tw-my-3 font-weight-bold"> Form <span className="tw-text-red-500">*</span></div>
                  <select
          onChange={(e) => this.onChangeForm(e, 1)}
            id="forms"
            className="form-control form-tag"
            name="forms">
            {forms.map((item) => (
              <option value={item.value}>{item.label}</option>
            ))}</select>        
                   {addtionalFilters.map((filter,index) => 
                      this.renderAddtionalFilters(filter, index)
                    )}
              </div>
            </div>
                {/* {
                  users.length ?
                  
                  <MultiUserSelector users={users} 
                  onChanged={value=>this.addItem(value.value,value.label)} 
                  select={selectedValues}
                    />
                  : 
                  ''
                } */}
                <ExportTypeSelector suspended={exportTypeFilters} 
                  onValueChanged={value=>this.saveData('display', value.value)} 
                  select={{label: 'Excel/CSV', value: 'excel'}} 
                  />
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
              <ResultPanel 
                isError={isError}
                errorMsg={errorMsg}
                reportBody={reportBody}
                reportHeaders={reportHeaders}
                isLoading={isLoading}
                isFirst={isFirst}
                recordperpage={recordperpage}
                total_page={total_page}
                pagenum={pagenum}
                display={display}
                sortStatus={sortStatus}
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