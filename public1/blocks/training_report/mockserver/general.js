const express = require('express');
const router = express.Router();

router.post('/mock/api/general.php', (req, res) => {

    if (!req.body.action) {
        res.send('Please define action.')
    }

    let data = {} // The data going to reurn to frontend

    switch (req.body.action) {
        case 'get_course_data':
            data= [
              {
                label: 'Course 1',
                value: '1',
                tagLabel: 'course',
                type: 'category',
                children: [
                  {
                    label: 'Course 1 Activity 1',
                    value: '10',
                    tagLabel: 'activity',
                    children: [
                      {
                        label: 'Course 1 Activity 1 Module 1',
                        value: '14',
                        tagLabel: 'module'
                      },
                      {
                        label: 'Course 1 Activity 1 Module 2',
                        value: '14',
                      },
                      {
                        label: 'Course 1 Activity 1 Module 2',
                        value: '14',
                      },
                    ],
                  },
                ],
                // checked,        // optional: Initial state of checkbox. if true, checkbox is selected and corresponding pill is rendered.
                // disabled,       // optional: Selectable state of checkbox. if true, the checkbox is disabled and the node is not selectable.
                // expanded,       // optional: If true, the node is expanded (children of children nodes are not expanded by default unless children nodes also have expanded: true).
                // className,      // optional: Additional css class for the node. This is helpful to style the nodes your way
                // tagClassName,
              },
              {
                label: 'Course 2',
                value: '2',
                children: [
                  {
                    label: 'Course 2 Activity 1',
                    value: '14',
                    children: [
                      {
                        label: 'Course 2 Activity 1 Module 1',
                        value: '16',
                      },
                      {
                        label: 'Course 2 Activity 1 Module 2',
                        value: '15',
                      },
                      {
                        label: 'Course 2 Activity 1 Module 2',
                        value: '18',
                      },
                    ],
                  },
                ],
              }
            ];
            res.send(data);
            break
        
        case 'get_hierarchy_data':
          data= [
            { 
              label:"MMSG",
              value:"1",    
              type:"LMS",
              parent: '#',
              children:[
                {
                    label:"Manager Team 1",
                    value:"3",
                    type:"Second level",    
                    parent: '1',
                    children:[{
                      label:"Sells Team 1",
                      value:"6",
                      type:"Third level",    
                      parent: '3',
                      },
                      {
                        label:"Sells Team 2",
                        value:"7",
                        type:"Third level",    
                        parent: '3',
                      }
              
                    ]
                },
                {
                  label:"Manager Team 2",
                    value:"4",
                    type:"Second level", 
                    parent: '1',   
                    children:[]
                }
              ]},
            ];
          res.send(data);
          break
        

        case 'get_config_info':
          data= {
            code: 0,
            bar_completed_color:"#CBDDE6",
            bar_not_completed_color:"#EEEEEE",
            client_logo:"http://localhost/mmsg/pluginfile.php/1/block_training_report/client_logo/0/test.png",
            course_overview_percentage_background_color:"#CBDDE6",
            course_overview_percentage_text_color:"#EEEEEE",
            display_user_default_fields:{
              country:{
                type:"menu",
                name:"Country",
                options:[
                  {
                    label:"Australia",
                    value: "Australia",
                  },
                  {
                    label:"USA",
                    value: "USA",
                  },
                  {
                    label:"England",
                    value: "England",
                  },
                  {
                    label:"France",
                    value: "France",
                  }
                  ]
                },
                city:{
                  type:"text",
                  name:null
                },
                lastaccess:{
                  type:"date",
                  name:"Last access"
                }
            },
             display_user_profile_fields:{
                Rolename:{
                   type:"text",
                   name:"Rolename"
                },
                Gender:{
                   type:"text",
                   name:"Gender"
                },
                EmployeeType:{
                   type:"text",
                   name:"Employee Type"
                },
                hobby:{
                   type:"menu",
                   name:"hobby",
                   options:[
                      "swim",
                      "sleep",
                      "ski",
                      "skate"
                   ]
                }
             },
             filter_user_default_fields:{
                email:{
                   type:"text",
                   "name":"Email"
                },
                country:{
                   type:"menu",
                   name:"Country"
                },
                lastaccess:{
                   type:"date",
                   name:"Last access"
                }
             },
             filter_user_profile_fields:{
                ReportTo:{
                   type:"text",
                   name:"Manager"
                },
                PositionTitle:{
                   type:"text",
                   name:"Position Title"
                },
                QUAL:{
                   type:"menu",
                   name:"Qualifications",
                   options:[
                      "Certificate",
                      "Diploma",
                      "Graduate Degree",
                      "Post-Graduate Degree",
                      "Vocational - Other"
                   ]
                }
             },
             pie_completed_color:"#CBDDE6",
             pie_completed_highlight_color:"#232800",
             pie_not_completed_color:"#EEEEEE",
             pie_not_completed_highlight_color:"#BCB8B8" 
          };
          res.send(data);
          break
        case 'get_test_data': 
            data = {
                foo: "hello",
                bar: "world"
            }

            res.send(data);
            break

        case 'get_test_user':
            // payload
            // {
            //  userid: 1,
            //  sesskey: 'VWerNwnmNm'
            // }

            data = {
                id: 888,
                firstname: "Foo",
                lastname: "Bar",
            }

            res.send(data);
            break
        
        case 'general':
          // payload =
          // {
          // //  userid: 1,
          // //  sesskey: 'VWerNwnmNm'
          // }

          data = {
            code: 0,
            page: 3,
            total_page: 5,
            per_page: 50, 
            headers:{
              full_name:"Full name",
              course_name:"Practice element/module",
              module_name:"Module",
              completion:"Completion",
              // enrolled_date:"Enrolled date",
              completion_date:"Completion date",
              Rolename:"Rolename", // elective headers from config
              Gender:"Gender",
              EmployeeType:"Employee Type",
              hobby:"hobby"
            },
            data:[
              {
                full_name:"Bela Hunter",
                course_name:"Ergonomics Test",
                module_name:"assign",
                completion:"Completed",
                enrolled_date:"04/07/2019",
                completion_date:"04/07/2019",
                Rolename:null,
                Gender:"F",
                EmployeeType:null,
                hobby:null,
                user_id:"3906"
              },
              {
                full_name:"Bela Hunter",
                course_name:"Ergonomics Test",
                module_name:"assign",
                completion:"Completed",
                enrolled_date:"04/07/2019",
                completion_date:"04/07/2019",
                Rolename:null,
                Gender:"F",
                EmployeeType:null,
                hobby:null,
                user_id:"3906"
              },
              {
                full_name:"Bela Hunter",
                course_name:"Ergonomics Test",
                module_name:"assign",
                completion:"Completed",
                enrolled_date:"04/07/2019",
                completion_date:"04/07/2019",
                Rolename:null,
                Gender:"F",
                EmployeeType:null,
                hobby:null,
                user_id:"3906"
              },
              {
                full_name:"Bela Hunter",
                course_name:"Ergonomics Test",
                module_name:"assign",
                completion:"Completed",
                enrolled_date:"04/07/2019",
                completion_date:"04/07/2019",
                Rolename:null,
                Gender:"F",
                EmployeeType:null,
                hobby:null,
                user_id:"3906"
              },
              {
                full_name:"Bela Hunter",
                course_name:"Ergonomics Test",
                module_name:"assign",
                completion:"Completed",
                enrolled_date:"04/07/2019",
                completion_date:"04/07/2019",
                Rolename:null,
                Gender:"F",
                EmployeeType:null,
                hobby:null,
                user_id:"3906"
              },
            ]
          }

        res.send(data);
        break
    }

})

module.exports = router;