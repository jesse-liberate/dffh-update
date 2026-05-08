# API Documentaion

## Category
[1  Get General Report]
[2. Get Course Overview Report]
[3. Get Activiy Report]
[4. Get Individual Report]
[5. Get User Report]
[6. Get Report filters]
[7. Get Config Data ]
[8. Get Form Data ]




## 1、 Get General Report

### Request URL：
	http://localhost:3000/blocks/reporting/api/general.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/general.php?course=20,30,40&completion=true
  &enrolled_from=04052015&enrolled_to=09102019&completion_from=04052015&completion_to=09102019&hierarchy=2145
  &sitename=test&city=MEL&suspended=1&recordperpage=50&pagenum=3&display=HTML -->

### Action Name
get_general_report

### Request Type：
  POST

### Payload Type

jz: site name, city, last access, confirm with Eric are the conpulsory or configriable?
jz: suspended, data type is bool. REVIEW DATA TYPES FOR ALL PARAMS AND RETURN

	|Param		               |Mandatory  |Type     |Info
	|course                  |Y          |array   |courseid array
  |completion              |Y          |string   |both, true, false
  |enrolled_from           |Y          |string   |enrolled date timestamp, from
  |enrolled_to             |Y          |string   |enrolled date timestamp, to
  |completion_from         |Y          |string   |completion date timestamp, from
  |completion_to           |Y          |string   |completion date timestamp, to
  |hierarchy               |N          |int      |hiearchy node
  |suspended               |Y          |int      |-1(only),0 (exclude), 1(include)
  |recordperpage           |Y          |int      |number of record
  |pagenum                 |Y          |int      |number of current page
  |display                 |Y          |string   |HTML, Excel, PDF


### Return Example：

{
    {
       "action":"general",
       "data":{
           code: 0,
           page: 3,
           total_page: 5,
           per_page: 50, 
          "headers":[
            {
              'key': 'full_name',
              'display': 'Full name',
            }
            {
              'key': 'course_name',
              'display': 'Course name',
            }
          ],
          "data":[
             {
                "full_name":"Bela Hunter",
                "course_name":"Ergonomics Test",
                "module_name":"assign",
                "completion":"Completed",
                "enrolled_date":"04/07/2019",
                "completion_date":"04/07/2019",
                "Rolename":null,
                "Gender":"F",
                "EmployeeType":null,
                "hobby":null,
                "user_id":"3906"
             },
          ]
       }
    }
}





## 2、Get Course Overview Report

### Request URL：
	http://localhost:3000/blocks/reporting/api/courseoverview.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/courseoverview.php?course=20,30,40&
  &enrolled_from=04052015&enrolled_to=09102019&completion_from=04052015&completion_to=09102019&hierarchy=2145
  &sitename=test&city=MEL&lastaccess=04052015,09102019&suspended=0&recordperpage=50&pagenum=3&display=HTML -->

### Request Type：
	POST

### Action Name
get_course_overview_report

### Payload Type

	|Param		               |Mandatory  |Type     |Info
	|course                  |Y          |string   |courseid
  |enrolled_from           |Y          |string   |enrolled date timestamp, from
  |enrolled_to             |Y          |string   |enrolled date timestamp, to
  |completion_from         |Y          |string   |completion date timestamp, from
  |completion_to           |Y          |string   |completion date timestamp, to
  |hierarchy               |N          |int      |hiearchy node
  |sitename                |N          |string   |site name
  |city                    |N          |string   |city name
  |suspended               |Y          |int      |-1(only),0 (exclude), 1(include)
  |recordperpage           |Y          |int      |number of record
  |pagenum                 |Y          |int      |number of current page
  |display                 |Y          |string   |HTML, Excel, PDF


### Return Example：

{
  code: 0,
  page: 3,
  total_page: 5,
  per_page: 50,
  overall_completed: 0.6,
  progress: [
    "4"=>{
      name:'test course 1',
      number: 15
    },
    "15"=>{
      name:'test course 2',
      number: 2
    },
    "451"=>{
      name:'test course 3',
      number: 45
    }
  ]
  header: {
        "fullname": "Full name",
        "coursename": "Course",
        "enrolment_startdate": "Enrolled Date",
        "completion_date": "Completion Date",
        "sitename": "Site Name",
        "city": "City",
        "last_access": "Last access"
  },
  body: [
        {
            "fullname": "John Doe",
            "coursename": "Test Course 1",
            "enrolled_date": "14/01/2021",
            "completion_date": "",
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
        {
            "fullname": "John Doe 2",
            "coursename": "Test Course 1",
            "enrolled_date": "15/01/2021",
            "completion_date": "15/01/2021",
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
        {
            "fullname": "John Doe 3",
            "coursename": "Test Course 1",
            "enrolled_date": "14/01/2021",
            "completion_date": "15/01/2021",
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
  ],
}


## 3、Get Activiy Report

### Request URL：
	http://localhost:3000/blocks/reporting/api/activity.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/courseoverview.php?course=20,30,40&enrolled_from=04052015&enrolled_to=09102019&hierarchy=2145&sitename=test&city=MEL&lastaccess=09102019&suspended=include&recordperpage=50&pagenum=3&display=HTML -->

### Request Type：
	POST

### Action Name
get_activity_report

### Payload Type

	|Param		               |Mandatory  |Type     |Info
	|course                  |Y          |string   |courseid
  |enrolled_from           |Y          |string   |enrolled date timestamp, from
  |enrolled_to             |Y          |string   |enrolled date timestamp, to
  |hierarchy               |N          |int      |hiearchy node
  |sitename                |N          |string   |site name
  |city                    |N          |string   |city name
  |suspended               |Y          |int      |-1(only),0 (exclude), 1(include)
  |recordperpage           |Y          |int      |number of record
  |pagenum                 |Y          |int      |number of current page
  |display                 |Y          |string   |HTML, Excel, PDF


### Return Example：
{
  code: 0,
  //page: 3,
  //total_page: 5,
  //per_page: 50,
  total_records_num: 30,
  header: {
            "fullname": "Full name",
            "enrolled_date": "Enrolled Date",
            "assignment": "Assignment",
            "sitename": "Site Name",
            "city": "City",
            "last_access": "Last Access"
  },
  body: [
    {
            "fullname": "John Doe",
            "enrolled_date": "14/01/2021",
            "assignment": true,
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
        {
            "fullname": "John Doe 2",
            "enrolled_date": "15/01/2021",
            "assignment": false,
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
        {
            "fullname": "John Doe 3",
            "enrolled_date": "14/01/2021",
            "assignment": true,
            "sitename": "Test Site",
            "city": "MEL",
            "last_access": "14/01/2021"
        },
  ]
}



## 4、Get Individual Report

### Request URL：
	http://localhost:3000/blocks/reporting/api/individual.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/individual.php?user=12&recordperpage=50&pagenum=3&display=HTML -->

### Request Type：
	POST

### Action Name
get_individual_report

### Payload Type

	|Param		            |Mandatory  |Type     |Info
	|user_id               |Y          |int      |selected user's id
  |display              |Y          |string   |HTML, Excel, PDF


### Return Example：

{
  code: 0,
  page: 3,
  total_page: 5,
  per_page: 50,
  header: {
            "fullname": "Full name",
            "coursename": "Course Name",
            "module": "Module",
            "completion": "Completion",
            "enrolled_date": "Enrolled Date",
            "completion_date": "Completion Date",
            "sitename": "Site Name"
  },
  body: [
    {
            "fullname": "John Doe",
            "coursename": "Test Course 1",
            "module": "Test Module 2",
            "completion": "Not Completed",
            "enrolled_date": "14/01/2021",
            "completion_date": "",
            "sitename": "Test Site",
        },
        {
            "fullname": "John Doe",
            "coursename": "Test Course 2",
            "module": "Test Module 2", 
            "completion": "Completed",
            "enrolled_date": "14/01/2021",
            "completion_date": "15/01/2021",
             "sitename": "Test Site",
        },
        {
            "fullname": "John Doe",
            "coursename": "Test Course 1",
            "module": "Test Module1",    
            "completion": "Completed",
            "enrolled_date": "14/01/2021",
            "completion_date": "18/01/2021",
            "sitename": "Test Site",
        },
  ]
}


##5. Users report

### Payload Type

	|Param		                                |Mandatory  |Type        |Info
  |hierarchy               |N          |int      |hiearchy node
  |suspended               |Y          |int      |-1(only),0 (exclude), 1(include)
  |recordperpage           |Y          |int      |number of record
  |pagenum                 |Y          |int      |number of current page
  |display                 |Y          |string   |HTML, Excel, PDF    

### Return Example
{
    "data":{
        "columns":[
           "User name",
           "First name",
           "Last name",
           "Email",
           "Preferred first name",
           "Gender",
           "Employee Type",
           "hobby"
        ],
        "users":{
           "2":{
              "User name":"admin",
              "First name":"Admin",
              "Last name":"User",
              "Email":"support@mindatlas.com",
              "Preferred first name":"admin",
              "Gender":"M",
              "Employee Type":"",
              "hobby":""
           },
           "2886":{
              "User name":"julier",
              "First name":"Julie",
              "Last name":"Conduit",
              "Email":"julie.conduit@maxxia.com.au",
              "Preferred first name":"Julie",
              "Gender":"F",
              "Employee Type":"",
              "hobby":""
           },
        }
     }
}




## 6、 Get filters info

### Request URL：
	http://localhost:3000/blocks/reporting/api/filters.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/filters.php?type=courseoverview -->
  
### Request Type：
	POST

### Action Name
get_filters_into

### Payload Type

	|Param		            |Mandatory  |Type         |Info
  |user_id                 |Y          |int       |user id


### Return Example：

{
  code: 0,
  user_list:[
    {
      "fullnam": "John Doe"
      "userid": 23,
      "email": "johndoe@gmail.com"
    },
    {
      "fullnam": "Geroge Doe"
      "userid": 3,
      "email": "Gerogedoe@gmail.com"
    },
    {
      "fullnam": "John test"
      "userid": 2,
      "email": "johntest@gmail.com"
    }
  ]
  hiearchy_tree: [
    {
      node_id: 1,
      node_name:"LMS",
      belong_to:'',
      user: [
        {
          "fullnam": "John Doe"
          "userid": 23,
          "email": "johndoe@gmail.com"
        },
        {
          "fullnam": "Geroge Doe"
          "userid": 3,
          "email": "Gerogedoe@gmail.com"
        },
      ]
    },
     {
      node_id: 4,
      node_name:"LMS",
      belong_to: 1,
      user: [
        {
          "fullnam": "John Doe"
          "userid": 23,
          "email": "johndoe@gmail.com"
        },
        {
          "fullnam": "Geroge Doe"
          "userid": 3,
          "email": "Gerogedoe@gmail.com"
        },
      ]
    },
  ],

  site_name: [
    {
      name: "ashby",
      id: 12
    },
    {
      name: "box hill",
      id: 16
    }
  ]
  
}




// some other api
jz: we also need data form input values in filters, such as course tree and hierarchy tree
jz: we also need to fetch cofig data, such as color, enable/disable pdf

## 7、 Get Config Data

### Request URL：
	http://localhost:3000/blocks/reporting/api/config.php

<!-- ### Example：
	http://localhost:3000/blocks/reporting/api/config.php
   -->
### Request Type：
	POST

### Action Name 
get_config_info

### Payload Type

	|Param		                                |Mandatory  |Type        |Info
  |pie_completed_color                 |Y          |string      |color code (eg. #000000)
  |pie_not_completed_color               |Y          |string      |color code
  |pie_completed_highlight_color       |Y          |string      |color code
  |pie_not_completed_highlight_color     |Y          |string      |color code
  |bar_completed_color                 |Y          |string      |color code
  |bar_not_completed_color               |Y          |string      |color code
  |course_overview_percentage_background_color          |Y          |string      |color code
  |course_overview_percentage_text_color          |Y          |string      |color code
  |report_pdf                               |Y          |string      |disabled enabled
  |page_orientation                         |Y          |string      |portrait, landscape
  |display_user_default_fields                |Y          |array      |
  |display_user_profile_fields                |Y          |string      |
  |filter_user_default_fields                |Y          |string      |
  |filter_user_profile_fields                |Y          |string      |
  
### Return Example：

  {
    code: 0,
    {
       "action":"get_config_info",
       "data":{
             "bar_completed_color":"#CBDDE6",
             "bar_not_completed_color":"#EEEEEE",
             "client_logo":"http://localhost/mmsg/pluginfile.php/1/block_training_report/client_logo/0/test.png",
             "course_overview_percentage_background_color":"#CBDDE6",
             "course_overview_percentage_text_color":"#EEEEEE",
             "display_user_default_fields":{
                "country":{
                   "type":"menu",
                   "name":"Country",
                   "options":[
                     {
                       'value': 'AF',
                        'label': 'Afghanistan'
                     },
                     {
                       'value': 'AL',
                        'label': 'Albania'
                     }
                   ]
                },
                "city":{
                   "type":"text",
                   "name":null
                },
                "lastaccess":{
                   "type":"date",
                   "name":"Last access"
                }
             },
             "display_user_profile_fields":{
                "Rolename":{
                   "type":"text",
                   "name":"Rolename"
                },
                "Gender":{
                   "type":"text",
                   "name":"Gender"
                },
                "EmployeeType":{
                   "type":"text",
                   "name":"Employee Type"
                },
                "hobby":{
                   "type":"menu",
                   "name":"hobby",
                   "options":[
                     {
                       'value': 'swim',
                        'label': 'swim'
                     },
                     {
                       'value': 'sleep',
                        'label': 'sleep'
                     }
                   ]
                }
             },
             "filter_user_default_fields":{
                "email":{
                   "type":"text",
                   "name":"Email"
                },
                "country":{
                   "type":"menu",
                   "name":"Country"
                },
                "lastaccess":{
                   "type":"date",
                   "name":"Last access"
                }
             },
             "filter_user_profile_fields":{
                "ReportTo":{
                   "type":"text",
                   "name":"Manager"
                },
                "PositionTitle":{
                   "type":"text",
                   "name":"Position Title"
                },
                "QUAL":{
                   "type":"menu",
                   "name":"Qualifications",
                   "options":[
                     {
                       'value': 'Certificate',
                        'label': 'Certificate'
                     },
                     {
                        'value': 'Diploma',
                        'label': 'Diploma'
                     },
                     {
                        'value': 'Graduate Degree',
                        'label': 'Graduate Degree'
                     },
                   ]
                }
             },
             "pie_completed_color":"#CBDDE6",
             "pie_completed_highlight_color":"#232800",
             "pie_not_completed_color":"#EEEEEE",
             "pie_not_completed_highlight_color":"#BCB8B8"
          }
    }
  }

## 8、 Get Form Data
### Request URL：

<!-- ### Example：
   -->
### Request Type：
	POST

### Action Name 
get_form_data

### Payload Type

	|Param		                                |Mandatory  |Type        |Info
    |report_type                                |Y          |string      |'general','course','activity','individual', 'user'
    
Note: Individual report user filter options are different

### Return Example
// not individual report
{
   "action":"get_form_data",
   "data":{
      <!-- "hierarchy_nodes":[      // This is our Old LMS hierarchy sturcture
               {
                  "id":"1",
                  "parent":"#",
                  "state":{
                     "selected":true
                  },
                  "text":"MMSG"
               },
               {
                  "id":"12",
                  "parent":"1",
                  "state":{
                     "selected":false
                  },
                  "text":"Brendan Maggs"
               },
       ], -->
       {
              "label":"My learning",
              "value":"1",
              "type":"category",
              "children":[
                 {
                    "label":"Adaptive Suite Consumer Law",
                    "value":"25",
                    "type":"course"
                 },
                 {
                    "label":"Asking Questions",
                    "value":"110",
                    "type":"course"
                 },
              ]
           }
       "hierarchy_nodes":[
            { 
              <!-- node name, 'label' is mandatory -->
              "label":"MMSG",
              <!-- node id, 'value' is mandatory -->
              "value":"1",    
              <!-- 'type', or others are customized value , not mandatory-->
              "type":"LMS",
              <!-- 'parent' is customized, not mandatoy -->
              "parent": '#',
              <!-- child node needs to be under childeren -->
              "children":[
                {
                   "label":"Manager Team 1",
                    "value":"3",
                    "type":"Second level",    
                    "parent": '1',
                    "children":[{
                      "label":"Sells Team 1",
                      "value":"6",
                      "type":"Third level",    
                      "parent": '3',
                      },
                      {
                        "label":"Sells Team 2",
                        "value":"7",
                        "type":"Third level",    
                        "parent": '3',
                      }
                     
                    ]
                },
                {
                  "label":"Manager Team 2",
                    "value":"4",
                    "type":"Second level", 
                    "parent": '1',   
                    "children":[]
                }
              ]},
       ]
      "root_node_id":"1",
      "courses":[
           {
              "label":"My learning",
              "value":"1",
              "type":"category",
              "children":[
                 {
                    "label":"Adaptive Suite Consumer Law",
                    "value":"25",
                    "type":"course"
                 },
                 {
                    "label":"Asking Questions",
                    "value":"110",
                    "type":"course"
                 },
              ]
           }
       ]
   }
}

//individual report
{
   "action":"get_form_data",
   "data":{
      "users":[
         {
            "id":"4702",
            "label":"Aaron Omsby (Aaron.Omsby@maxxia.com.au)"
         },
      ]
   }
}