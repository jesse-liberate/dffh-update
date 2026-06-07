import '@scss/layouts/mytrainingsessions.scss'
import React from "react";
import axios from 'axios';
import TrainingSessionTable from '../global/Trainingsessiontable';
class MyTrainingSessionsPage extends React.Component {

  constructor(props) {
    super();
        this.state = {
            data: []
        };
  }
  componentDidMount() {
    axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
      action: 'dffh_get_user_sessions',
      payload: {
        userid: M.user.id,
        sesskey: M.user.sesskey,
        $limit: 1, 
        $extrafields: [],
        $include_past: false
      },
    }).then(res => {
      this.setState({
        data: res.data
    });
    })
  }
  render() {  
   window.top.alert(this.state.data);
    if (this.state.data != []) {
      var trainingComponent =  <TrainingSessionTable data={this.state.data}></TrainingSessionTable>
    } else {
      var trainingComponent = null;
    }
  
    return (
      
      <div id="react-mytrainingsession">
       {trainingComponent}
      </div>
    );
  }
}

export default MyTrainingSessionsPage;