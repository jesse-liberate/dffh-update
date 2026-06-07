import React from "react";
import SectionWelcome from "./SectionWelcome";
import SectionCourses from "./SectionCourses";
import SectionBanner from "./SectionBanner";

function Frontpage(props) {
    return (
        <div id="react-frontpage">
            <SectionWelcome></SectionWelcome>
            <SectionCourses></SectionCourses>
            <SectionBanner></SectionBanner>
        </div>
    );
}

export default Frontpage;
