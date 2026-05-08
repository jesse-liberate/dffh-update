const express = require('express')
const cors = require('cors')
const app = express()
const PORT = 5555

// import routers
const general = require('./general')
const activity = require('./activity')

app.use(express.json())
app.use(cors())

// use routers
app.use(general)
app.use(activity)

app.listen(PORT, ()=>{
    console.log('Mock server listening on  localhost:' + PORT + '/ \n')
});