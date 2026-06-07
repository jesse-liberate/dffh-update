import React from 'react'
import axios from 'axios'

export default class Profile extends React.Component {
    constructor(props) {
        super();
        this.state = {
            user: M.user
        };
        console.log(props.target)
    }



    render() {
        let editLink = M.cfg.wwwroot + (M.user.isSiteAdmin ? '/user/editadvanced.php?id=' : '/user/edit.php?id=') + this.props.target.id + '&course=1&returnto=profile'

        const OrganisationOrAgency = this.props.target.profile.OrganisationOrAgency?this.props.target.profile.OrganisationOrAgency:'Empty';
        const RoleOrPosition = this.props.target.profile.RoleOrPosition?this.props.target.profile.RoleOrPosition:'Empty';
        const Program = this.props.target.profile.Program?this.props.target.profile.Program:'Empty';
        const IsPractitioner = this.props.target.profile.IsPractitioner?this.props.target.profile.IsPractitioner:'Empty';
        const WorkingSites = this.props.target.profile.LocalAreas?this.props.target.profile.LocalAreas.replace('\n', ", "):'Empty';

        return (
            <div className="user-details-wrapper py-4">
                <div className="row">
                    <div className="col-md-3 text-center">
                        <div dangerouslySetInnerHTML={{ __html: this.props.target.avatarL }} />
                        {(M.user.isSiteAdmin || M.user.id == this.props.target.id) &&
                            <a href={editLink} className="d-block btn btn-edit btn-primary my-3 mx-auto">EDIT PROFILE</a>
                        }

                        {M.user.isSiteAdmin && M.user.id != this.props.target.id && !this.props.target.isSiteAdmin &&
                            <a href={M.cfg.wwwroot + '/course/loginas.php?id=1&user=' + this.props.target.id + '&sesskey=' + M.user.sesskey} className="d-block mb-3">Login as</a>
                        }
                    </div>
                    <div className="col-md-9">
                        <div className="h1 font-weight-bold color-primary mb-4">{this.props.target.firstname} {this.props.target.lastname}</div>
                        <div className="profile-fileds row">
                            <div className="col-md-6">
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Email address</div>
                                    <div className="value color-brand-2">{this.props.target.email}</div>
                                </div>
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Organisation</div>
                                    <div className="value color-brand-2">{OrganisationOrAgency}</div>
                                </div>
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Role / Position</div>
                                    <div className="value color-brand-2">{RoleOrPosition}</div>
                                </div>
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Which program do you work under?</div>
                                    <div className="value color-brand-2">{Program}</div>
                                </div>
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Are you a practitioner, team leader or CP Navigator delivering the Response?</div>
                                    <div className="value color-brand-2">{IsPractitioner}</div>
                                </div>
                                <div className="field mb-3">
                                    <div className="title font-weight-bold">Local Area(s)</div>
                                    <div className="value color-brand-2">{WorkingSites}</div>
                                </div>
                            </div>
                            <div className="col-md-6">

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        )
    }
}