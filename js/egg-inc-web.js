const express = require('express');
const bodyParser = require('body-parser');
const createError = require('http-errors');

require('dotenv').config();
let _ = require('lodash')

const EggIncApi = require('./egg-inc-api');

// web server around the API
// setup express
const app = express().set('env', process.env.NODE_ENV || 'development');
app.set('json escape', true)
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));

// define end points
app.get('/Periodicals', (req, res, next) => {
    EggIncApi.getPeriodicals().then((data) => {
        res.send(data);
    }).catch((error) => {
        console.error(error);
        return next(new createError.InternalServerError(error));
    });
});

app.get('/getCoopStatus', (req, res, next) => {
    EggIncApi.getCoopStatus(req.query.contract, req.query.coop).then((data) => {
        res.send(data.data);
    }).catch((error) => {
        // console.error(error);
        // console.log(error.toJSON())
        return next(new createError.InternalServerError(error));
    });
});

app.get('/getPlayerInfo', (req, res, next) => {
    EggIncApi.getPlayerInfo(req.query.playerId).then((data) => {
        data.backup.contracts.archive = _.chain(data.backup.contracts.archive)
            .filter((activeContract) => {
                let goals = activeContract.contract.goals ? activeContract.contract.goals : activeContract.contract.gradeSpecs[0].goals

                if (activeContract.contract.identifier == 'f1-trs-2023') {
                    console.log(activeContract, goals)
                }

                return activeContract.numGoalsAchieved == goals.length
            })
            .map((activeContract) => {
                return activeContract.contract.identifier;
            })
            .toJSON()

        if (data.backup.contracts.contracts) {
            data.backup.contracts.contracts.forEach(activeContract => {
                let goals = activeContract.contract.goals ? activeContract.contract.goals : activeContract.contract.gradeSpecs[0].goals

                if (activeContract.numGoalsAchieved == goals.length) {
                    data.backup.contracts.archive.push(activeContract.contract.identifier)
                }
            })
        }

        data.backup.contracts.activeContracts = null;
        data.backup.contracts.contractIdsSeen = null;
        data.backup.farms = null

        res.json(data.backup);
    }).catch((error) => {
        console.error(error);
        return next(new createError.InternalServerError(error));
    });
});

app.get('/getPlayerInfos', (req, res, next) => {
    // read input in various forms
    let ids = [];
    const { playerIds, playerIdsJoined} = req.query;
    if (playerIds) { // individual ids
        if (Array.isArray(playerIds)) {
            ids = ids.concat(playerIds);
        } else {
            ids.push(playerIds);
        }        
    }
    if (playerIdsJoined) { // comma separated ids
        ids = ids.concat(playerIdsJoined.split(','));
    }
    // run
    EggIncApi.getPlayerInfos(ids).then((data) => {
        res.json(data);
    }).catch((error) => {
        console.error(error);
        //return next(error);
        return next(new createError.InternalServerError(error));
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
http://localhost:6001/Periodicals
http://localhost:6001/getCoopStatus?contract=new-moon&coop=dmv
http://localhost:6001/getPlayerInfo?playerId=EI6411720689451008
http://localhost:6001/getPlayerInfos?playerIdsJoined=EI6411720689451008,EI6411720689451008
http://localhost:6001/getPlayerInfos?playerIds[]=EI6411720689451008&playerIds[]=EI6411720689451008
http://localhost:6001/getPlayerInfos?playerIdsJoined=EI5426893308821504,EI6006465308917760,EI6622592762380288,EI6243749694275584,EI6670048183189504,EI5293412581900288,EI5889385330900992,EI4950801673355264
*/
