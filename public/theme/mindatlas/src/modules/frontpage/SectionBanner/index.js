import React from 'react'
// import axios from 'axios'

export default class SectionBanner extends React.Component {
    constructor(props) {
        super();
        this.state = {
            banner: {
                img: M.theme.brand.home_banner1_img,
                title: M.theme.brand.home_banner1_title,
                text: M.theme.brand.home_banner1_text,
                btn: M.theme.brand.home_banner1_btn,
                link: M.theme.brand.home_banner1_link
            }
        };
    }

    render() {

        let boxStyle = {
            backgroundImage: 'url("' + this.state.banner.img + '")',
        }

        return (
            <section id="section-banner" className="container-fluid row no-gutters px-0 ">
                <div className="banner-box bg-center-cover col-md-6" style={boxStyle} >

                </div>
                <div className="banner-box bg-color-brand-1 col-md-6 position-relative">
                    <div className=" text-white banner-content p-5 ">
                      <div className="title h1 font-weight-bolder">{this.state.banner.title}</div>
                      <div className="text mb-4">{this.state.banner.text}</div>
                      <div>
                          <a className="btn btn-primary d-inline-block" href={this.state.banner.link}>{this.state.banner.btn}</a>
                      </div>
                    </div>
                    {/* <div className="container">
                        <div className="row no-gutters py-5 ">
                            
                        </div>
                    </div> */}

                </div>
            </section>
        )
    }
}