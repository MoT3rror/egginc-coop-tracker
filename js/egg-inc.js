const b = require('base64-arraybuffer')
const axios = require('axios')
var protobuf = require("protobufjs")
var root = protobuf.loadSync('js/Proto/egginc.proto');

function ei_request(path, payload, requestPB, responsePB) {
    return new Promise((resolve, reject) => {
        var errMsg = requestPB.verify(payload);
        if (errMsg)
            throw Error(errMsg)
        
        var buffer = requestPB.encode(requestPB.create(payload)).finish()

        let options = {
            url : `http://afx-2-dot-auxbrainhome.appspot.com/${path}`,
            method : 'post',
            data: 'data=' + b.encode(buffer),
        }

        axios(options).then((response) => {
            let byteArray = new Array(0)
            protobuf.util.base64.decode(response.data, byteArray, 0)

            var FirstContactResponse = root.lookupType('FirstContactResponsePayload');

            resolve(responsePB.decode(byteArray))
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
        var payload = {
            clientVersion: 27,
            platform: 2,
            eiUserId: argv.playerId,
            deviceId: '1',
            username: '',
            gamesServicesId: 'a_1',
            rinfo: {
                eiUserId: argv.playerId,
                clientVersion: 27,
                version: '1.20.1',
                platform: 'ANDROID'
            }
        }

        ei_request(
            'ei/first_contact',
            payload,
            root.lookupType('FirstContactRequestPayload'),
            root.lookupType('FirstContactResponsePayload')
        ).then((data) => {
            console.log(JSON.stringify(data.payload.data))
        })
    })
    .command('events', 'Get Current Events', (yargs) => {}, (argv) => {
        eggIncApi.getPeriodicals().then((data) => {
            console.log(JSON.stringify(data))
        })
    })
    .command('test', 'Test', (yargs) => {}, (argv) => {
        var payload = {
            clientVersion: 27,
            platform: 2,
            eiUserId: 'EI4529978912276480',
            deviceId: '1',
            username: '',
            gamesServicesId: 'a_1',
            rinfo: {
                eiUserId: 'EI4529978912276480',
                clientVersion: 27,
                version: '1.20.1',
                platform: 'ANDROID'
            }
        }

        ei_request(
            'ei/first_contact',
            payload,
            root.lookupType('FirstContactRequestPayload'),
            root.lookupType('FirstContactResponsePayload')
        ).then((data) => {
            console.log(JSON.stringify(data.payload.data))
        })
    })
    .help()
    .argv
