import React, { Component } from 'react'
import ReactPaginate from 'react-paginate';
import {updateObject} from '../shared/utility'
// https://github.com/AdeleD/react-paginate#readme

export default class ResultPanel extends Component {

  constructor(props){
    super(props);

    this.pageChange = this.pageChange.bind(this);
  }

  setOrderBy=(key)=>{


    const elementsIndex = this.props.sortStatus.findIndex(element => element.key == key )
    let newArray = [...this.props.sortStatus]
    let newOrderBy = []
    if(!this.props.sortStatus[elementsIndex].order){
      this.props.sortStatus.forEach(v => {
        v.order = null;
      })
      newArray[elementsIndex] = {...newArray[elementsIndex], order: 'asc'}
      // newOrderBy = [key:]
    }else if(this.props.sortStatus[elementsIndex].order=='asc'){
      newArray[elementsIndex] = {...newArray[elementsIndex], order: 'desc'}
    }else if(this.props.sortStatus[elementsIndex].order=='desc'){
      newArray[elementsIndex] = {...newArray[elementsIndex], order: 'asc'}
    }
    // newArray[elementsIndex] = {...newArray[elementsIndex], order: !newArray[elementsIndex].order}
    newOrderBy = newArray[elementsIndex]
    this.props.updateReportState({sortStatus:newArray, order_by:newOrderBy, pagenum:1})

  }

  renderTableHeader(reportHeaders){
    // let headers = Object.values(reportHeaders)
    // console.log(reportHeaders)
    // let sortBtn = classNames({
    //   'table-sort': true,
    //   'order-asc': this.props.sortStatus[index].order=="asc",
    //   'order-desc': this.props.sortStatus[index].order=="desc"
    // });
    return reportHeaders.map((header, index) => {
      return <th 
      className="border tw-p-3 tw-text-left tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider"
      key={index}>
        <span className="table-header-title">{header.display}</span> 
        <span className={
            "table-sort " +
            (this.props.sortStatus[index].order=="asc" ? "order-asc " : " ") +
            (this.props.sortStatus[index].order=="desc" ? "order-desc ": " ") +
            (this.props.sortStatus[index].order!="desc" && this.props.sortStatus[index].order!="asc" ? "inactive ": " ")
        } onClick={() => this.setOrderBy(header.key)}></span>
      </th>
    })
  }

  pageChange({ selected }){
    // console.log(selected)
    this.props.updateReportState({pagenum: selected + 1})
  }

  

  renderTableBody(reportBody,reportHeaders){
    return reportBody.map((reportData, index) => {
      return (
        <tr key={index}>
          {reportHeaders.map((header,i)=>{
            if(header.key=="course_name" || header.key=="course_module_instance_name"){
              return (
                <td className="border tw-px-2 tw-py-1 tw-text-xs tw-whitespace-nowrap"
                key={i}>{reportData[header.key]?reportData[header.key]: ''}</td>
              )
            }else{

            }

            return (
                <td className="border tw-px-2 tw-py-1 tw-text-xs tw-whitespace-wrap"
                key={i}>{reportData[header.key]?reportData[header.key]: ''}</td>
            )
        })}
        </tr>
      )
    })
  }
  
  render() {
    const {reportBody, reportHeaders, reportCode, isLoading, 
      isFirst, sortStatus,recordperpage,total_page,pagenum,display,
      submittedDisplay, isError, errorMsg} = this.props
    // const {reportHeaders} = this.props
    // let headers = Object.values(reportHeaders)
    return (
      <div className="resultPanel tw-w-full">
        {
          isFirst ? <span>Please select in the left section and click 'Submit'</span> : 
          isLoading? <span>Loading...</span> :
          isError ? <span>{errorMsg}</span>:
          display=="HTML" ||  submittedDisplay?
            <div>
              <div  className="tw-overflow-auto">
                  {
                    reportBody && reportBody.length >=1 ?
                    <table className="tw-table-auto tw-min-w-full tw-divide-y tw-divide-gray-200">
                      <thead className="tw-bg-gray-50">
                      <tr>
                        {this.renderTableHeader(reportHeaders)}
                      </tr>
                      </thead>
                      <tbody className="tw-bg-white tw-divide-y tw-divide-gray-200">
                        {this.renderTableBody(reportBody,reportHeaders)}
                      </tbody>
                    </table>
                    :
                    <h3>No data has been found under selected filters.</h3>
                  }
                
              </div>
              <div className="tw-mt-3">
                {
                  reportBody&& reportBody.length >=1 ?
                  <ReactPaginate 
                    pageCount={total_page}
                    initialPage={pagenum-1}
                    containerClassName="pagination"
                    previousClassName="page-item"
                    nextClassName="page-item"
                    pageClassName="page-item"
                    pageLinkClassName="page-link"
                    previousLinkClassName="page-link"
                    nextLinkClassName="page-link"
                    activeClassName="activePagination"
                    pageRangeDisplayed={recordperpage}
                    marginPagesDisplayed='3'
                    onPageChange={this.pageChange}
                    disableInitialCallback={true}/>
                    :

                  ''
                }
                
              </div>
            </div>
            :
            <h3>Download finshed. Please check the file in 'Downloads'.</h3>
          
        }
        {/* { reportCode === 0 ?
          <div className="pagination">
            <ReactPaginate />
          </div>
          :
          ''
        } */}
          
      </div>
     )
  }
}
