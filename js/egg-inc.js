// const eggIncApi = require('./egg-inc-api/egginc_api.js')
// const ei = require('./egg-inc-api/egginc_pb')
const b = require('base64-arraybuffer')
const axios = require('axios')
const ei2 = require('./egg-inc-api2/myproto_libs')

function ei_request(path, message, responsePB) {
    return new Promise((resolve, reject) => {
        let options = {
            url : `http://afx-2-dot-auxbrainhome.appspot.com/ei/${path}`,
            method : 'get'
        }
        if (message) {
            options.method = 'post';
            options.data = 'data=' + b.encode(message.serializeBinary())
        }
        console.log(b.encode(message.serializeBinary()))
        axios(options).then((response) => {
            let byteArray = b.decode(response.data);
            let msgInstance = responsePB.deserializeBinary(byteArray);
            resolve(msgInstance.toObject());
        }).catch(err => {
            reject(err);
        })
    })
}

require('yargs')
    .scriptName('Egg Inc API')
    .usage('$0 <cmd> [args]')
    .command('getAllActiveContracts', 'Get All Active Contracts', (yargs) => {}, (argv) => {
        eggIncApi.getContractAll().then((contracts) => {
            console.log(JSON.stringify(contracts))
        })
    })
    .command('getCoopStatus', 'Get Coop Status', (yargs) => {
        yargs
            .positional('contract', {type: 'string'})
            .positional('coop', {type: 'string'})
    }, (argv) => {
        eggIncApi.getContract(argv.contract, argv.coop).then((coopInfo) => {
            console.log(JSON.stringify(coopInfo))
        })
    })
    .command('getPlayerInfo', 'Get Player Info', (yargs) => {
        yargs
            .positional('playerId', {type: 'string'})
    }, (argv) => {
        eggIncApi.getPlayerData(argv.playerId).then((player) => {
            console.log(JSON.stringify(player))
        })
    })
    .command('events', 'Get Current Events', (yargs) => {}, (argv) => {
        eggIncApi.getPeriodicals().then((data) => {
            console.log(JSON.stringify(data))
        })
    })
    .command('test', 'Test', (yargs) => {}, (argv) => {
        let message = new ei2.FirstContactRequestPayload()
        
        message.setApiVersion(27)
        message.setPlatform(2)
        message.setPlayerId('EI6411720689451008')
        message.set5('91cd4c812d1bc300')
        message.set6('')
        message.set7('a_46273246726295594')

        let userInfo = new ei2.FirstContactRequestPayload.UserInfo()
        userInfo.setPlayerId('EI6411720689451008')
        userInfo.setApiVersion(27)
        userInfo.setClientVersion('1.20.0')
        userInfo.setPlatform('ANDROID')
        message.setUserinfo(userInfo)

        ei_request('first_contact', message, ei2.FirstContact.Payload)
            .then(response => { console.log(response)});
    })
    .help()
    .argv
