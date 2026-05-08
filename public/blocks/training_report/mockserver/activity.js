const express = require('express');
const router = express.Router();

router.post('/mock/api/activity.php', (req, res) => {

    if (!req.body.action) {
        res.send('Please define action.')
    }

    let data = {} // The data going to reurn to frontend

    switch (req.body.action) {
        case 'get_activities':
            data = [
                {
                    id: 1,
                    name: "a 1"
                },
                {
                    id: 2,
                    name: "a 2"
                }
            ]

            res.send(data);
            break

    }

})

module.exports = router;