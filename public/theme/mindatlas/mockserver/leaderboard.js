const express = require('express');
const router = express.Router();

router.post('/blocks/leadership/api/index.php', (req, res) => {

    if (!req.body.action) {
        res.send('Please define action.')
    }

    let data = {} // The data going to reurn to frontend

    switch (req.body.action) {
        case 'get_my_ranks': 
            // action: 'get_my_ranks',
            // payload: {
            //     userid: 1,
            //     sesskey: 'VWerNwnmNm'             
            // },

            // return user's rank and 2 person upper and lower,
            // if user is top 5/bottom 5, return top 5/bottom 5

            data = [
                {
                    rank: 40,
                    userid: 2,
                    fullname: 'Jacky Zhu',
                    points: 70,
                },
                {
                    rank: 41,
                    userid: 3,
                    fullname: 'Tanya Scott',
                    points: 65,
                },
                {
                    rank: 42,
                    userid: 4,
                    fullname: 'John Citizen',
                    points: 50,
                },
                {
                    rank: 43,
                    userid: 5,
                    fullname: 'Jerry Cottle',
                    points: 35,
                },
                {
                    rank: 44,
                    userid: 6,
                    fullname: 'Jason Lee',
                    points: 30,
                },
            ]

            res.send(data);
            break


    }

})

module.exports = router;