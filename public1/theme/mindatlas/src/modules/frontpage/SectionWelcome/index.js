import React from "react";
import axios from "axios";
import Welcome from "./Welcome";

export default class SectionWelcome extends React.Component {
    constructor(props) {
        super();
        this.state = {
            progress: 0,
            completed: [],
            not_completed: [],
        };
    }

    componentDidMount() {
        // axios.post(`http://localhost:5555/blocks/theme_support/api/index.php`, {
        axios
            .post(`${M.cfg.wwwroot}/blocks/theme_support/api/user.php`, {
                action: "get_user_learning_progress",
                payload: {
                    userid: M.user.id,
                    sesskey: M.user.sesskey,
                },
            })
            .then((res) => {
                this.setState({
                    progress: res.data.progress,
                    completed: res.data.completed,
                    not_completed: res.data.not_completed,
                });
            });
    }

    render() {
        let sectionWelcomeStyle = {
            backgroundImage: 'url("' + M.theme.brand.home_introbg + '")',
        };

        return (
            <section
                id="section-welcome"
                className="container-fluid text-white"
                style={sectionWelcomeStyle}
            >
                {/* <div className="shadow-layer"></div> */}
                <div className="container welcome-wrapper">
                    <Welcome></Welcome>
                </div>
            </section>
        );
    }
}
