import React from 'react'
import axios from 'axios'

export default class Leaderboard extends React.Component {
    constructor(props) {
        super();
        this.state = {
            ranks: []
        };
    }

    componentDidMount() {
        // axios.post(`http://localhost:5555/blocks/leadership/api/index.php`, {
            axios.post(`${M.cfg.wwwroot}/blocks/coursepoints/api/coursepoints.php`, {
            action: 'get_my_ranks',
            payload: {
                userid: M.user.id,
                sesskey: M.user.sesskey
            },
        }).then(res => {
            
            this.setState({
                ranks: Object.values(res.data)
            });
            // console.log(this.state.ranks);
        })
    }


    render() {

        return (
            <div id="block-leaderboard">
                <div className="title mb-3">
                    <div className="h3 d-inline-block font-weight-bold">LEADERBOARD</div>
                    <a href={M.cfg.wwwroot + "/blocks/coursepoints/seeall.php?tab=lms"} className="btn btn-primary float-right">VIEW ALL</a>
                </div>
                <div className="ranks-table container">
                    <div className="row header bg-white py-2 text-dark">
                        <div className="col-3">RANK</div>
                        <div className="col-6">FULL NAME</div>
                        <div className="col-3">POINTS</div>
                    </div>

                {this.state.ranks.length <=3 ?
                        <div className="body">
                        {
                            this.state.ranks.map(rank => (
                                <div className="row rank py-4" key={rank.rank}>
                                    <div className="col-3">{rank.rank}</div>
                                    { rank.userid == M.user.id ? 
                                    <div className="col-6"> <span className="bg-color-brand-2">{rank.fullname}</span></div>
                                    :
                                    <div className="col-6 bg-color-"><span>{rank.fullname}</span></div>}
                                    {/* <div className="col-6">{rank.fullname}</div> */}
                                    <div className="col-3">{rank.points}</div>
                                </div>
                            ))
                        }
                        </div>
                    :
                        <div className="body">
                        {
                            this.state.ranks.map(rank => (
                                <div className="row rank py-2" key={rank.rank}>
                                    <div className="col-3">{rank.rank}</div>
                                    { rank.userid == M.user.id ? 
                                    <div className="col-6"> <span className="bg-color-brand-2">{rank.fullname}</span></div>
                                    :
                                    <div className="col-6 bg-color-"><span>{rank.fullname}</span></div>}
                                    {/* <div className="col-6">{rank.fullname}</div> */}
                                    <div className="col-3">{rank.points}</div>
                                </div>
                            ))
                        }
                        </div>
                    }

                </div>
            </div>
        )
    }
}