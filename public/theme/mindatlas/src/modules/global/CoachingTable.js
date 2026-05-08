import React from "react";
import axios from 'axios';
class TrainingSessionTable extends React.Component{

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
          action: 'dffh_get_user_sessions_coaching',
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

    render (){
        return (<>
        <></>
            <table className="training-table w-100">
                <tbody>
                    <tr className="training-table-header">
                        {/* <td>TRAINING MODULE</td> */}
                        <td>COACH</td>
                        <td>LOCATION</td>
                        <td>START DATE</td>
                        <td>END DATE</td>
                        <td>TIME</td>
                        <td>DURATION</td>
                        <td>COACH LINK</td>
                        {/* <td className="button-head">REGISTER/WAITLIST</td> */}
                        
                    </tr>
                    {this.state.sessions.map((item) =>
                        <tr key={item.id} className="">
                            <td > {item.coach} </td> 
                            <td> {item.location}</td>
                            <td> {item.timestart}</td>
                            <td>{item.timefinish}</td>
                            <td>{item.time} </td>
                            <td>{item.duration} </td>
                            <td> {item.link != false && (<a
          style={{ color: 'white' }}
          className="btn-request-session"
          href={item.link}>
          Manage session
        </a> )} {item.duration == false && (
          <p>Only available for coach </p>
        )} </td>
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
export default TrainingSessionTable;