const b = require('base64-arraybuffer')
const axios = require('axios')
var protobuf = require("protobufjs")

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
        protobuf.load('js/Proto/egginc.proto', function(err, root) {
            if (err)
                throw err;

            var FirstContact = root.lookupType("FirstContactRequestPayload")

            var payload = {
                apiVersion: 27,
                platform: 2,
                playerId: 'EI4529978912276480',
                '_5': '1',
                '_6': '',
                '_7': 'a_1',
                userInfo: {
                    playerId: 'EI4529978912276480',
                    apiVersion: 27,
                    clientVersion: '1.20.0',
                    platform: 'ANDROID'
                }
            }
            var errMsg = FirstContact.verify(payload);
            if (errMsg)
                throw Error(errMsg)

            var message = FirstContact.create(payload)

            var buffer = FirstContact.encode(message).finish();

            let options = {
                url : `http://afx-2-dot-auxbrainhome.appspot.com/ei/first_contact`,
                method : 'post',
                data: 'data=' + b.encode(buffer)
            }

            axios(options).then((response) => {
                let byteArray = new Array(0)
                protobuf.util.base64.decode(response.data, byteArray, 0)

                var FirstContactResponse = root.lookupType('FirstContact');

                console.log(JSON.stringify(FirstContactResponse.decode(byteArray)))
                return;
            }).catch(err => {
                console.log(err)
            })
        })
    })
    .help()
    .argv
