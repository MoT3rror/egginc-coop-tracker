const express = require('express');
const bodyParser = require('body-parser');
const createError = require('http-errors');

require('dotenv').config();

const EggIncApi = require('./egg-inc-api');

// web server around the API
// setup express
const app = express();
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));

// define end points
app.get('/getAllActiveContracts', (req, res) => {
    EggIncApi.getPeriodicals().then((data) => {
        res.send(data.periodicals.contracts);
    });
});

app.get('/events', (req, res) => {
    EggIncApi.getPeriodicals().then((data) => {
        res.send(data.periodicals.events);
    });
});

app.get('/getCoopStatus', (req, res) => {
    EggIncApi.getCoopStatus(req.query.contract, req.query.coop).then((data) => {
        res.send(data.status);
    });
});

app.get('/getPlayerInfo', (req, res) => {
    EggIncApi.getPlayerInfo(req.query.playerId).then((data) => {
        res.send(data.payload.data);
    });
});

app.get('/notFound', (/*req, res, next*/) => {
    throw new createError.NotFound();
});

(async function () {
    console.log('Starting!');

    // start web server
    app.listen(process.env.EI_API_PORT, () => {
        console.log(`App started, port: ${process.env.EI_API_PORT}, running as: ${process.env.NODE_ENV}`);
    });
})()

module.exports = app;

/*
Usage examples:
npm run start-ei-web
http://localhost:6001/getAllActiveContracts
http://localhost:6001/events
http://localhost:6001/getCoopStatus?contract=new-moon&coop=dmv
http://localhost:6001/getPlayerInfo?playerId=EI6411720689451008
*/
