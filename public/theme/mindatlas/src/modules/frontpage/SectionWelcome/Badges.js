import React from 'react'
import axios from 'axios'

export default class Frontpage extends React.Component {
    constructor(props) {
        super();
        this.state = {
            total: 0,
            badges: []
        };
        // this.handleClick = this.handleClick.bind(this);
    }


    componentDidMount() {
        // axios.post(`http://localhost:5555/blocks/theme_support/api/index.php`, {
        axios.post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
            action: 'get_user_badges',
            payload: {
                userid: M.user.id,
                sesskey: M.user.sesskey
            },
        }).then(res => {
            this.setState({
                total: res.data.total,
                badges: res.data.badges
            });
        })
    }


    render() {

        return (
            <div id="block-badges" className="mt-5">
                <div className="color-brand-2 mb-1 font-weight-bold"><span>BADGES</span></div>
                <div className="">
                    <div className="box mr-2 float-left">
                        <div className="h1 font-weight-bold mb-1 mt-2 lh-100">{this.state.total}</div>
                        <div><small><b>COLLECTED</b></small></div>
                    </div>
                    {this.state.badges.map((badge, index)=>{
                        return (
                            <a key={index} href={badge.link} className="box float-left mr-2 d-inline-block">
                                <img src={badge.image} className="w-100" alt={badge.name} title={badge.name} />
                            </a>
                        )
                    })}
                    
                    <a className="btn btn-primary mt-3 float-right" href={M.cfg.wwwroot+'/badges/mybadges.php'}>VIEW ALL</a>
                </div>
            </div>


        )
    }
}