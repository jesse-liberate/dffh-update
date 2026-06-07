import React from 'react';
import axios from 'axios';
import LearningProgress from "../../coursecategory/LearningProgress";

export default class Welcome extends React.Component {
    constructor(props) {
        super();
        this.state = {
            progress: 0,
            completed: [],
            not_completed: [],
            welcome_title: M.theme.brand.home_welcome_title,
            welcome_text: M.theme.brand.home_welcome_text,
            welcome_text_2: M.theme.brand.home_welcome_text_2,
        };
    }

    componentDidMount() {

        // axios.post(`http://localhost:5555/blocks/theme_support/api/index.php`, {
        axios.post('${M.cfg.wwwroot}/blocks/theme_support/api/user.php', {
            action: 'get_user_learning_progress',
            payload: {
                userid: M.user.id,
                sesskey: M.user.sesskey
            },
        }).then(res => {

            this.setState({
                progress: res.data.progress,
                completed: res.data.completed,
                not_completed: res.data.not_completed,
            });
        })
    }

    render() {

        let barInnerStyle = {
            width: this.state.progress + '%'
        }

        return (
            <div id="block-welcome">
                <div className="intro d-flex my-4">
                    <div className="userimage mr-3 mb-3">
                        <div
                            dangerouslySetInnerHTML={{ __html: M.user.avatarL }}
                        />
                    </div>
                    <div className="message color-white lh-120">
                        <div className="h1 font-weight-bolder color-brand-1">
                            {this.state.welcome_title} {M.user.firstname}
                        </div>
                        <div
                            className="welcome-text-1 color-deep"
                            dangerouslySetInnerHTML={{
                                __html: this.state.welcome_text,
                            }}
                        />
                        <div
                            className="text-dark"
                            dangerouslySetInnerHTML={{
                                __html: this.state.welcome_text_2,
                            }}
                        />
                    </div>
                </div>
                <LearningProgress></LearningProgress>
            </div>
        );
    }
}