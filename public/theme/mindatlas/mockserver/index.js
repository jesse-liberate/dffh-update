const express = require("express");
const cors = require("cors");
const app = express();
const PORT = 5555;

// import routers
const theme_support = require("./theme_support");

app.use(express.json());
app.use(cors());

// use routers
app.use(theme_support);

app.listen(PORT, () => {
    console.log("Mock server listening on  localhost:" + PORT + "/ \n");
});
