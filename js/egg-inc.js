const b = require('base64-arraybuffer')
const axios = require('axios')
var protobuf = require("protobufjs")
var root = protobuf.loadSync('js/Proto/egginc.proto');

let ei_request = (path, payload, requestPB, responsePB) => {
    return new Promise((resolve, reject) => {
        var errMsg = requestPB.verify(payload);
        if (errMsg)
            throw Error(errMsg)
        
        var buffer = requestPB.encode(requestPB.create(payload)).finish()

        let options = {
            url : `https://afx-2-dot-auxbrainhome.appspot.com/${path}`,
            method : 'post',
            data: 'data=' + b.encode(buffer),
        }

        axios(options).then((response) => {
            let byteArray = new Array(0)
            protobuf.util.base64.decode(response.data, byteArray, 0)

            resolve(responsePB.decode(byteArray))
        }).catch(err => {
            reject(err);
        })
    })
}

let getPeriodicals = () => {
    var payload = {
        userId: 'EI6411720689451008',
        currentClientVersion: 30,
        rinfo: {
            eiUserId: 'EI6411720689451008',
            clientVersion: 30,
            version: '1.20.7',
            platform: 'ANDROID'
        }
    }

    return ei_request(
        'ei/get_periodicals',
        payload,
        root.lookupType('GetPeriodicalsRequestPayload'),
        root.lookupType('GetPeriodicalsResponsePayload')
    )
}


require('yargs')
    .scriptName('Egg Inc API')
    .usage('$0 <cmd> [args]')
    .command('getAllActiveContracts', 'Get All Active Contracts', (yargs) => {}, (argv) => {
        getPeriodicals().then((data) => {
            console.log(JSON.stringify(data.periodicals.contracts))
        })
    })
    .command('getCoopStatus', 'Get Coop Status', (yargs) => {
        yargs
            .positional('contract', {type: 'string'})
            .positional('coop', {type: 'string'})
    }, (argv) => {
        var payload = {
            contractId: argv.contract,
            code: argv.coop
        }

        ei_request(
            'ei/coop_status',
            payload,
            root.lookupType('CoopStatusRequestPayload'),
            root.lookupType('CoopStatusResponsePayload')
        ).then((data) => {
            console.log(JSON.stringify(data.status))
        })
    })
    .command('getPlayerInfo', 'Get Player Info', (yargs) => {
        yargs
            .positional('playerId', {type: 'string'})
    }, (argv) => {
        var payload = {
            clientVersion: 30,
            platform: 2,
            eiUserId: argv.playerId,
            username: '',
            rinfo: {
                eiUserId: argv.playerId,
                clientVersion: 30,
                version: '1.20.7',
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
        return getPeriodicals().then((data) => {
            console.log(JSON.stringify(data.periodicals.events))
        })
    })
    .help()
    .argv
