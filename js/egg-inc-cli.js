const EggIncApi = require('./egg-inc-api');
const _ = require('lodash')

// cli around the API
require('yargs')
    .scriptName('Egg Inc API')
    .usage('$0 <cmd> [args]')
    .command('getAllActiveContracts', 'Get All Active Contracts', (yargs) => {}, (argv) => {
        EggIncApi.getPeriodicals().then((data) => {
            console.log(JSON.stringify(data.periodicals.contracts))
        })
    })
    .command('getCoopStatus', 'Get Coop Status', (yargs) => {
        yargs
            .positional('contract', {type: 'string'})
            .positional('coop', {type: 'string'})
    }, (argv) => {
        EggIncApi.getCoopStatus(argv.contract, argv.coop).then((data) => {
            console.log(JSON.stringify(data))
        })
    })
    .command('getPlayerInfo', 'Get Player Info', (yargs) => {
        yargs
            .positional('playerId', {type: 'string'})
    }, (argv) => {
        EggIncApi.getPlayerInfo(argv.playerId).then((data) => {   
            data.data.contracts.completeContracts = _.chain(data.data.contracts.pastContracts)
                .filter((activeContract) => {
                    return activeContract.numGoalsCompleted == activeContract.props.rewards.length
                })
                .map((activeContract) => {
                    return activeContract.props.id
                })
                .toJSON()
            ;
            
            data.data.contracts.activeContracts = null;
            data.data.contracts.pastContracts = null
            console.log(JSON.stringify(data.data))
        })
    })
    .command('getPlayerInfos', 'Get Player Infos', (yargs) => {
        yargs
            .positional('playerIds', {type: 'string'}) // comma separated list of ids
    }, (argv) => {
            EggIncApi.getPlayerInfos(argv.playerIds.split(',')).then((data) => {
            console.log(JSON.stringify(data))
        })
    })
    .command('events', 'Get Current Events', (yargs) => {}, (argv) => {
        return EggIncApi.getPeriodicals().then((data) => {
            console.log(JSON.stringify(data.periodicals.events))
        })
    })
    .help()
    .argv

/*
Usage examples:
node js/egg-inc-cli.js events
node js/egg-inc-cli.js getAllActiveContracts
node js/egg-inc-cli.js getCoopStatus --contract new-moon --coop dmv
node js/egg-inc-cli.js getPlayerInfo --playerId EI6411720689451008
node js/egg-inc-cli.js getPlayerInfos --playerIds EI6411720689451008,EI6411720689451008
*/
