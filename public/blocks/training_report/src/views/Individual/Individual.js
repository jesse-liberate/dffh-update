import React from 'react';
import axios from 'axios'
import { imgUrlToImgData } from '../../shared/utility';
import ResultPanel from "../../components/resultPanel";
import FilterPanel from "../../components/filterPanel";
import UserSelector from '../../components/UserSelector';
import ExportTypeSelector from '../../components/ExportTypeSelector';
import fileDownload from 'js-file-download'
import jsPDF from "jspdf";
import Worker from "../Worker/file.worker";
export default class Individual extends React.Component{

  constructor(props){
    super(props);

    this.state = {
        tableData: [],
        users:[],
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
        submittedDisplay: false,
        logoUrl: '',
    }

  }

  componentDidMount(){
    // get_form_data
    axios.post(`${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'get_form_data',
      payload: {
        report_type:'individual'
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
        logoUrl: logoUrl
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
      case 'HTML':
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedUserId} onClick={()=>this.renderTable('new')}>Submit</button>;
      case 'excel':
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedUserId} onClick={this.exportCSV}>Download</button>;
      case 'pdf':
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedUserId} onClick={this.exportPDF}>Download</button>;
      default:
        return <button className="btn btn-primary tw-float-right" disabled={!this.state.selectedUserId} onClick={()=>this.renderTable('new')}>Submit</button>;
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

  renderTable = (type) => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true, submittedDisplay: true})
      if(type=='new'){
        this.setState({pagenum:1})
      }
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'individual',
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

  }

  exportCSV = () => {
    //send current selected data to BE, get data back
      this.setState({isFirst:false, isLoading: true})
      // axios.post(Lib.mock_api_path.general, {
      axios.post( `${M.cfg.wwwroot}/blocks/training_report/api/report.php`, {
      action: 'individual',
      payload: {
        user_id: this.state.selectedUserId,
        recordperpage: this.state.recordperpage,
        pagenum:this.state.pagenum,
        suspended: this.state.suspended,
        display:'excel',
        order_by: this.state.order_by
      },
    }).then(res => {
      fileDownload(res.data, 'individual.csv');
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
      action: 'individual',
      payload: {
        user_id: this.state.selectedUserId,
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
      this.exportPDFFile(res.data.data.headers,res.data.data.data,"Individual Report","Individual Report.pdf");
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
    const {users,reportHeaders,reportBody,isLoading,isFirst,erroMsg,
      recordperpage,total_page,pagenum,exportTypeFilters, display, order_by,
      sortStatus,submittedDisplay,isError,errorMsg} = this.state

    const selectedUser = users.find((user) => user.value == M.cfg.userid);
    
    return (
      <div className="row">
      <div className="filter-zone col-lg-3">
          <div className="w-100 tw-p-3">
              <FilterPanel>
                {
                  users.length ?
                  <UserSelector users={users} 
                    onValueChanged={value=>this.saveData('selectedUserId', value.value)} 
                    select={selectedUser}
                    />
                  : 
                  ''
                }
                <ExportTypeSelector suspended={exportTypeFilters} 
                  onValueChanged={value=>this.saveData('display', value.value)} 
                  select={{label: 'HTML', value: 'HTML'}} 
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