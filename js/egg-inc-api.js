const b = require('base64-arraybuffer')
const axios = require('axios')
const protobuf = require("protobufjs")
const root = protobuf.loadSync('js/Proto/egginc.proto');

const ei_request = (path, payload, requestPB, responsePB) => {
    return new Promise((resolve, reject) => {
        var errMsg = requestPB.verify(payload);
        if (errMsg)
            throw Error(errMsg)

        var buffer = requestPB.encode(requestPB.create(payload)).finish()

        console.log(payload)

        let options = {
            // url: 'https://afx-2-dot-auxbrainhome.appspot.com/' + path,
            url: `https://www.auxbrain.com/${path}`,
            method: 'post',
            data: 'data=' + b.encode(buffer),
        }
        console.log(options.data)

        axios(options).then((response) => {
            console.log(response.data)

            let byteArray = new Array(0)
            protobuf.util.base64.decode(response.data, byteArray, 0)
            console.log(responsePB.decode(byteArray))

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
            root.lookupType('PeriodicalsResponseData')
        );
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
