import React from "react";
import axios from 'axios';

class RequestSessionTable extends React.Component{

    constructor(props) {
        super();
        this.state = {
            total: 0,
            sessions: [],
            categories:[],
            wwwroot: M.cfg.wwwroot,
            isSiteAdmin: M.user.isSiteAdmin,
            selectedCategory : 0,
            searchText : '',
            sessionStatus: {
                '-1': 'Past events',
                '10' :'Book now',
                '20':'Cancelled',
                '30':'Declined',
                '40':'Requested',
                '50':'Approved',
                '60':'Waitlisted',
                '70':'Booked',
                '80':'Not show',
                '90': 'Partially attended',
                '100':'Fully attended'
            }
        };
    }

    componentDidMount() {
        axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
          action: 'dffh_get_user_requests',
          payload: {
            userid: M.user.id,
            sesskey: M.user.sesskey,
            $limit: 1, 
            $extrafields: [],
            $include_past: false
          },
        }).then(res => {
          this.setState({
            sessions: res.data[0]
        });
      
        })
      }
    
   
      handleClick = (request_id) => {
        axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
            action: 'dffh_cancel_request',
            payload: {
              userid: M.user.id,
              sesskey: M.user.sesskey,
              request_id: request_id,
            },
          }).then(res => {
            window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/my_coaching_sessions.php';
          })
      }
      modifySession = (request_id) => {
          window.location.href = M.cfg.wwwroot + '/theme/mindatlas/pages/detail-requested-training-sessions.php?request=' + request_id;
      }
    
  
    render (){
        
            return (<>
                <></>
                    <table className="training-table w-100">
                        <tbody>
                            <tr className="training-table-header">
                                {/* <td>TRAINING MODULE</td> */}
                                <td style={{ width: '25%' }}>COACH</td>
                                <td style={{ width: '25%' }}>START DATE</td>
                                <td style={{ width: '25%' }}>TIME</td>
                             <td style={{ width: '25%' }} className="button-head">ACTIONS</td> 
                                
                            </tr>
                            {this.state.sessions && this.state.sessions.map((item) =>
                                <tr key={item.request_id} className="">
                                    <td> {item.coach_firstname} {item.coach_lastname}</td> 
                                    <td> {item.date}</td>
                                    <td> {item.time}</td>
                                    <td>   <button style={{ color: 'white' }}
                  className="btn-request-session modify-btn" onClick={() => this.modifySession(item.request_id)}>Edit</button>
                  <button style={{ color: 'white' }}
                  className="btn-request-session bg-danger modify-btn" onClick={() => this.handleClick(item.request_id)}>Cancel</button></td>
                                    {/* <td className="button-td mt-2">
                                        {item.status != null ?  <a href={this.state.wwwroot + '/theme/mindatlas/pages/sessions_details.php?id=' + item.id} className=" btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold mb-2 "> {item.status}</a>   : <a className="btn p-3 btn-primary">Book</a>}
                                        {item.statuscancel != null ?  <a href={this.state.wwwroot + '/theme/mindatlas/pages/sessions_details.php?id=' + item.id} className=" btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold mb-2"> {item.statuscancel}</a>   : ''}
                                    </td> */}
                                </tr>
                            )}
                        </tbody>
                     </table>
                </>)
        
        
    }

}
export default RequestSessionTable;