const express = require("express");
const router = express.Router();

router.post("/blocks/theme_support/api/index.php", (req, res) => {
    // need 1 php function in blocks/theme_support/lib.php
    // theme_support_get_user_course_progress($userid, $courseid) {
    //     return 0 - 100 int
    // }

    // need 1 php function in blocks/theme_support/lib.php
    // theme_support_get_user_course($userid, $courseid) {
    // return {
    // course_id: 2,
    // category_id: 1,
    // fullname: 'Sample course 1',
    // shortname: 'Sample course 1',
    // idnumber: '',
    // summary: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', // plain text
    // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
    // progress: 90, // user's progress in this course, 0 - 100, int,
    // rate: 3.5, // 0-5, float, 1 decimal place,
    // enrol_time: 1601254210,
    // }
    // }

    if (!req.body.action) {
        res.send("Please define action.");
    }

    let data = {}; // The data going to reurn to frontend

    switch (req.body.action) {
        case "get_cart":
            // get current user's shopping cart by session, can be guest user
            // payload
            // {
            // }
            data = {
                cartItems: [
                    {
                        id: 15,
                        courseImage:
                            "http://localhost/physiosports/theme/mindatlas/pix/pic11.jpg",
                        course_title: "Induction",
                        quantity: 2,
                        price: 100,
                        subtotal: 1.2,
                        type: "coupon",
                    },
                    {
                        id: 22,
                        courseImage:
                            "http://localhost/physiosports/theme/mindatlas/pix/pic11.jpg",
                        course_title: "Marketing Training",
                        quantity: 3,
                        price: 200,
                        subtotal: 10,
                        type: "course",
                    },
                ],
                discount: null,
                totalPrice: 50,
            };
            res.send(data);
            break;

        case "get_user_dashboard_courses":
            // payload
            // {
            //     userid: 1,
            //     sesskey: 'VWerNwnmNm',
            // }

            // NOTE:
            // 1. return courses user enrolled into and are active
            // 2. only include 3 type of activities
            // a. facetoface: upcoming events,
            // b. workshop: activity tagged as 'workshop'
            // c. lecture: activity tagged as 'lecture'
            data = [
                {
                    id: 2,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 1 & 2",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    activities: {
                        facetoface: [
                            {
                                name: "offline training 1",
                                venue: "Atura Blacktown, 32 Cricketers Arms Road Prospect 2148",
                                date: "3 February 2021",
                                time: "12:30 PM - 1:30 PM",
                                link: "https://honda-s.mindatlas.com/mod/facetoface/view.php?id=2351",
                            },
                        ],
                        workshop: [
                            {
                                name: "workshop 1",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                        lecture: [
                            {
                                name: "lecture 1",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                    },
                },
                {
                    id: 3,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 3 & 4",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/08/My-Post-2-300x300.jpg",
                    activities: {
                        facetoface: [
                            {
                                name: "offline training 3 & 4",
                                venue: "Atura Blacktown, 32 Cricketers Arms Road Prospect 2148",
                                date: "3 February 2021",
                                time: "12:30 PM - 1:30 PM",
                                link: "https://honda-s.mindatlas.com/mod/facetoface/view.php?id=2351",
                            },
                        ],
                        workshop: [
                            {
                                name: "workshop 3 & 4",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                        lecture: [
                            {
                                name: "lecture 3 & 4",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                    },
                },
                {
                    id: 4,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 5 & 6",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/08/My-Post-4-300x300.jpg",
                    activities: {
                        facetoface: [
                            {
                                name: "offline training 5 & 6",
                                venue: "Atura Blacktown, 32 Cricketers Arms Road Prospect 2148",
                                date: "3 February 2021",
                                time: "12:30 PM - 1:30 PM",
                                link: "https://honda-s.mindatlas.com/mod/facetoface/view.php?id=2351",
                            },
                        ],
                        workshop: [
                            {
                                name: "workshop 5 & 6",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                        lecture: [
                            {
                                name: "lecture 5 & 6",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                    },
                },
                {
                    id: 5,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 7",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/08/My-Post-4-300x300.jpg",
                    activities: {
                        facetoface: [
                            {
                                name: "offline training 7",
                                venue: "Atura Blacktown, 32 Cricketers Arms Road Prospect 2148",
                                date: "3 February 2021",
                                time: "12:30 PM - 1:30 PM",
                                link: "https://honda-s.mindatlas.com/mod/facetoface/view.php?id=2351",
                            },
                        ],
                        workshop: [
                            {
                                name: "workshop 7",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                        lecture: [
                            {
                                name: "lecture 7",
                                thumbnail:
                                    "http://localhost/LMS/physiosports/pluginfile.php/23/mod_page/content/2/samplephoto-mindatlasdoor.jpg",
                                link: "http://localhost/LMS/physiosports/mod/page/view.php?id=2",
                            },
                        ],
                    },
                },
            ];

            res.send(data);
            break;

        case "get_available_courses":
            // payload: {

            // }
            data = [
                {
                    id: 21,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 21",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "100",
                },
                {
                    id: 22,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 22",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "150",
                },
                {
                    id: 23,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 23",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "150",
                },
                {
                    id: 24,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 24",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "200",
                },
                {
                    id: 25,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 25",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "250",
                },
            ];

            res.send(data);
            break;

        case "get_course_catalogue":
            // payload {

            // }

            // return all courses, no matter enrolled or not, sort by created time DESC

            data = [
                {
                    id: 21,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 21",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "100",
                },
                {
                    id: 22,
                    name: "ONLINE Science of Cycling:‘Physio Bikefit’ Level 22",
                    img: "http://physiosports.com.au/wp-content/uploads/2020/01/john-arano-h4i9G-de7Po-unsplash-1-300x300.jpg",
                    price: "150",
                },
            ];

            res.send(data);
            break;

        case "get_user_learning_progress":
            // action: 'get_user_learning_progress',
            // payload: {
            //     userid: 1,
            //     sesskey: 'VWerNwnmNm'
            // },
            data = {
                progress: 100, // 0 - 100, int, amoung all available modules with completion
                // all courses sort by enroll time,  form latest to ealierest
                completed: [
                    {
                        course_id: 2,
                        category_id: 1,
                        fullname: "Completed course 1",
                        shortname: "Completed course 1",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/1.jpg",
                        progress: 90, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254210,
                    },
                    {
                        course_id: 3,
                        category_id: 1,
                        fullname: "Completed course 3",
                        shortname: "Completed course 3",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/2.jpg",
                        progress: 30, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254510,
                    },
                    {
                        course_id: 4,
                        category_id: 1,
                        fullname: "Completed course 4",
                        shortname: "Completed course 4",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/3.jpg",
                        progress: 30, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254510,
                    },
                    {
                        course_id: 5,
                        category_id: 1,
                        fullname: "Completed course 5",
                        shortname: "Completed course 5",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/4.jpg",
                        progress: 30, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254510,
                    },
                ],
                not_completed: [
                    {
                        course_id: 6,
                        category_id: 1,
                        fullname: "Not completed course 1",
                        shortname: "Not completed course 1",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/2.jpg",
                        progress: 90, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254210,
                    },
                    {
                        course_id: 7,
                        category_id: 1,
                        fullname: "Not completed course 3",
                        shortname: "Not completed course 3",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/3.jpg",
                        progress: 90, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254210,
                    },
                    {
                        course_id: 8,
                        category_id: 1,
                        fullname: "Not completed course 4",
                        shortname: "Not completed course 4",
                        idnumber: "",
                        summary:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.", // plain text
                        // course_image: 'http://generic.local/pluginfile.php/27/course/overviewfiles/almonds-berries-blackberries-1099680.jpg',
                        course_image:
                            "/generic/theme/mindatlas/pix/photo/4.jpg",
                        progress: 90, // user's progress in this course, 0 - 100, int,
                        rate: 3.5, // 0-5, float, 1 decimal place,
                        enrol_time: 1601254210,
                    },
                ], // return empty array [], if no result
            };

            res.send(data);
            break;

        case "get_user_badges":
            // payload
            // {
            //  userid: 1,
            //  sesskey: 'VWerNwnmNm'
            // }

            data = {
                total: 3, // total number of badges
                badges: [
                    {
                        name: "Sample badge 1",
                        image: "http://generic.local/pluginfile.php/1/badges/badgeimage/1/f1",
                        link: "http://generic.local/badges/badge.php?hash=4caeabf70a04daf0edea2f5fadd907a629644a41",
                    },
                    {
                        name: "Sample badge 2",
                        image: "http://generic.local/pluginfile.php/1/badges/badgeimage/1/f1",
                        link: "http://generic.local/badges/badge.php?hash=4caeabf70a04daf0edea2f5fadd907a629644a41",
                    },
                    {
                        name: "Sample badge 3",
                        image: "http://generic.local/pluginfile.php/1/badges/badgeimage/1/f1",
                        link: "http://generic.local/badges/badge.php?hash=4caeabf70a04daf0edea2f5fadd907a629644a41",
                    },
                ],
            };

            res.send(data);
            break;

        case "get_course_info":
            //payload
            //
            // payload: {
            //     courseId: this.props.id,
            // };
            data = {
                image: "/generic/theme/mindatlas/pix/photo/1.jpg",
                fullname: "Test Course",
                // rating: 3.5,
                summary:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.",
                // rateNumber: 2,
                // viewNumber: 6
            };
            res.send(data);
            break;

        case "get_user_profile":
            // payload: {
            //     userid: 1,
            // },
            data = {
                id: 1,
                avatar: "http://generic.local/user/editadvanced.php?id=2&course=1&returnto=profile",
            };
    }
});

module.exports = router;
