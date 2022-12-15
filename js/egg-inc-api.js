const b = require('base64-arraybuffer')
const axios = require('axios')
const protobuf = require("protobufjs");
const pako = require('pako');
const fernet = require('fernet');
const root = protobuf.loadSync('js/Proto/egginc.proto');

const ei_request = (path, payload, requestPB, responsePB) => {
    return new Promise((resolve, reject) => {
        var errMsg = requestPB.verify(payload);
        if (errMsg)
            throw Error(errMsg)

        var buffer = requestPB.encode(requestPB.create(payload)).finish()

        let options = {
            url: `https://www.auxbrain.com/${path}`,
            method: 'post',
            data: 'data=' + b.encode(buffer),
        }

        axios(options).then((response) => {
            let byteArray = new Array(0)
            protobuf.util.base64.decode(response.data, byteArray, 0)

            resolve(responsePB.toObject(responsePB.decode(byteArray), {
                longs: String,
                enums: String,
                bytes: String,
            }))
        }).catch(err => {
            reject(err);
        })
    })
}

class EggIncApi {
    static getCoopStatus(contract, coop) {
        const payload = {
            contractIdentifier: contract,
            coopIdentifier: coop,
            userId: 'EI6411720689451008',
            rinfo: {
                eiUserId: 'EI6411720689451008',
                clientVersion: 45,
                version: '1.25.4',
                platform: 'ANDROID',
            }
        }

        return ei_request(
            'ei/coop_status',
            payload,
            root.lookupType('ContractCoopStatusRequest'),
            root.lookupType('ContractCoopStatusResponseData')
        );
    }

    static getPeriodicals() {
        const payload = {
            userId: 'EI6411720689451008',
            currentClientVersion: 45,
            rinfo: {
                eiUserId: 'EI6411720689451008',
                clientVersion: 45,
                version: '1.25.4',
                platform: 'ANDROID',
            }
        }

        return ei_request(
            'ei/get_periodicals',
            payload,
            root.lookupType('GetPeriodicalsRequest'),
            root.lookupType('AuthenticatedMessage')
        ).then(data => {
            let example = 'gAAAAABjmp_g_g5sxbNDhhKSoLQDBAZ0os1a5-Av4VR8ci1vA1jflOPYY0rGS9ZwVlrl1m8Hk2KBnJroQgpXiSO8Wir0aluXU10GWmAi9IzTsO_3qqPbN-8='
            // var secretString = Buffer.from('THE SECRETS OF THE UNIVERSE WILL').toString('base64url')
            var secretString = 'CNj4uO/kp27pDj2SnqqOACFtp8bTnME9Gdcsf+idVn0='
            var secret = new fernet.Secret(secretString)
            console.log(secretString, secret)
            var token = new fernet.Token({
                secret: secret,
                token: example,
                ttl: 0
            })
            console.log(token.decode());


            
            let strData = Buffer.from(data.message, 'base64')

            var binData = new Uint8Array(strData);
            var data        = pako.inflate(binData);

            let responsePB = root.lookupType('PeriodicalsResponse')

            return responsePB.toObject(responsePB.decode(data), {
                longs: String,
                enums: String,
                bytes: String,
            })
        });
    }

    static getPlayerInfo(playerId) {
        var payload = {
            botName: 'EggBert',
            eiUserId: playerId,
        }

        return ei_request(
            'ei/bot_first_contact',
            payload,
            root.lookupType('EggIncFirstContactRequest'),
            root.lookupType('EggIncFirstContactResponse')
        );
    }

    static getPlayerInfos(playerIds) {
        const tasks = playerIds.map(playerId => this.getPlayerInfo(playerId));
        return Promise.all(tasks);
    }
}

module.exports = EggIncApi;
