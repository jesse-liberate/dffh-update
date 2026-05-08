import React from "react";
import axios from "axios";

export default class SectionResources extends React.Component {
    constructor(props) {
        super();
        this.state = {
            videos: [],
            podcast: [],
            pdf: [],
        };
    }

    componentDidMount() {
        axios
            .post(`${M.cfg.wwwroot}/blocks/resources/api/index.php`, {
                action: "get_recommended_resources",
                payload: {
                    userid: M.user.id,
                    sesskey: M.user.sesskey,
                },
            })
            .then((res) => {
                console.log(res.data);
                this.setState({
                    videos: res.data.video,
                    podcast: res.data.podcast,
                    pdf: res.data.pdf,
                });
            });
    }

    render() {
        function renderResourceItem(item, index) {
            return (
                <a key={index} className="item d-block" href={item.view_url}>
                    {/* <img src={item.img_url} className="img" /> */}
                    <span className="name">{item.name}</span>
                </a>
            );
        }

        return (
            <section
                id="section-resources"
                className="container-fluid py-5 bg-light"
            >
                <div className="container">
                    <div className="clearfix mb-3">
                        <div className="h2 color-brand-2 font-weight-bold float-left">
                            MY RESOURCES
                        </div>
                        <a
                            href={
                                M.cfg.wwwroot +
                                "/theme/mindatlas/pages/file_library.php"
                            }
                            className="btn btn-primary float-right"
                        >
                            RESOURCE LIBRARY
                        </a>
                    </div>

                    <div className="row">
                        <div className=" res-box col-md-4 video">
                            <div className="title bg-color-brand-1">
                                <div className="video-icon"></div>
                                <div className="d-inline-block">VIDEOS</div>
                            </div>
                            {this.state.videos.length > 0 ? (
                                <div className="items">
                                    {this.state.videos.map((item, index) =>
                                        renderResourceItem(item, index)
                                    )}
                                </div>
                            ) : (
                                <div className="message text-center">
                                    <h3>No Resources yet.</h3>
                                </div>
                            )}
                        </div>
                        <div className=" res-box col-md-4 podcast">
                            <div className="title bg-color-brand-1">
                                <div className="podcast-icon"></div>
                                <div className="d-inline-block">PODCAST</div>
                            </div>
                            {this.state.podcast.length > 0 ? (
                                <div className="items">
                                    {this.state.podcast.map((item, index) =>
                                        renderResourceItem(item, index)
                                    )}
                                </div>
                            ) : (
                                <div className="message text-center">
                                    <h3>No Resources yet.</h3>
                                </div>
                            )}
                        </div>
                        <div className=" res-box col-md-4 pdf">
                            <div className="title bg-color-brand-1">
                                <div className="pdf-icon"></div>
                                <div className="d-inline-block">PDF</div>
                            </div>
                            {this.state.pdf.length > 0 ? (
                                <div className="items">
                                    {this.state.pdf.map((item, index) =>
                                        renderResourceItem(item, index)
                                    )}
                                </div>
                            ) : (
                                <div className="message text-center">
                                    <h3>No Resources yet.</h3>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        );
    }
}
