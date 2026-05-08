const express = require('express');
const router = express.Router();

router.post('/blocks/resources/api/index.php', (req, res) => {

    if (!req.body.action) {
        res.send('Please define action.')
    }

    let data = {} // The data going to reurn to frontend

    switch (req.body.action) {
        case 'get_recommended_resources': 
            // action: 'get_frontpage_resources',
            // payload: {}

            // use hacer version, same data structure
            // return the latest 3 item for each type

            data = {
                videos: [
                    {
                        id: 1,
                        name: 'Sample video 1',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 2,
                        name: 'Sample video 2',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 3,
                        name: 'Sample video 3',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                ],
                podcast: [
                    {
                        id: 4,
                        name: 'Sample MP3 1',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 5,
                        name: 'Sample MP3 2',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 6,
                        name: 'Sample MP3 3',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                ],
                pdf: [
                    {
                        id: 7,
                        name: 'Sample pdf 1',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 8,
                        name: 'Sample pdf 2',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                    {
                        id: 9,
                        name: 'Sample pdf 3',
                        desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        desc_plain: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non dui lectus. Suspendisse a dui vel tortor imperdiet placerat.',
                        img_url: 'https://www.flaticon.com/svg/static/icons/svg/1126/1126900.svg',
                        view_url: 'http://generic.local'
                    },
                ]
            }

            res.send(data);
            break


    }

})

module.exports = router;