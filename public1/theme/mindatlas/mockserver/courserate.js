const express = require('express');
const router = express.Router();

router.post('/blocks/courserate/api/index.php', (req, res) => {

    // block_courserate_get_course_rate($courseid, $userid) {
    //     return {
            //     courseid: 2,
            //     rate: '4.5', // float, 1 decimal place
            //     canrate: true,  // bool, false if user has rated already
            //     ratetimes: 100, // int, how many times rated 
            //     viewtimes: 200, // int, how many times viewd 
            // }
    // }


    if (!req.body.action) {
        res.send('Please define action.')
    }

    let data = {} // The data going to reurn to frontend

    switch (req.body.action) {

        // JZ: handled by moodle event in backend
        // case 'view_course': 
        //     // action: 'view_course',
        //     // payload: {
        //     //      courseid: 2,
        //     //      userid: 2,
        //     //      sesskey: 'VWerNwnmNm',
        //     // }

        //     // BE: course viewed times ++

        //     data = {}

        //     res.send(data);
        //     break    

        case 'get_course_rate': 
            // action: 'get_course_rate',
            // payload: {
            //      courseid: 2,
            //      userid: 2,
            //      sesskey: 'VWerNwnmNm',
            // }

            data = {
                courseid: 2,
                rating: '4.5', // float, 1 decimal place
                canrate: true,  // bool, false if user has rated already
                ratetimes: 100, // int, how many times rated 
                viewtimes: 200, // int, how many times viewd,
                userrate: '2.2' // float, 1 decimal place
            }

            res.send(data);
            break

        case 'rate_course': 
            // action: 'rate_course',
            // payload: {
            //      courseid: 2,
            //      userid: 2,
            //      sesskey: 'VWerNwnmNm',
            //      rate: 5, // 0 - 5, int
            // }
            //Please check if the user has already rated this course before, and choose whether to create rate
            //or update rate
            //after write into db, please
            //return the newest rate info
            data = {
                courseid: 2,
                rating: '3.1', // float, 1 decimal place
                canrate: true,  // bool, false if user has rated already
                ratetimes: 101, // int, how many times rated 
                viewtimes: 202, // int, how many times viewd 
                userrate: '2.5' // float, 1 decimal place
            }

            res.send(data);
            break


    }

})

module.exports = router;