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

        let options = {
            url: `http://afx-2-dot-auxbrainhome.appspot.com/${path}`,
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
            contractId: contract,
            code: coop
        }

        return ei_request(
            'ei/coop_status',
            payload,
            root.lookupType('CoopStatusRequestPayload'),
            root.lookupType('CoopStatusResponsePayload')
        );
    }

    static getPeriodicals() {
        const payload = {
            userId: 'EI6411720689451008',
            currentClientVersion: 45,
            rinfo: {
                // eiUserId: 'EI6411720689451008',
                clientVersion: 45,
                version: '1.22.2',
                platform: 'ANDROID'
            }
        }

        return ei_request(
            'ei/get_periodicals',
            payload,
            root.lookupType('GetPeriodicalsRequestPayload'),
            root.lookupType('GetPeriodicalsResponsePayload')
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
            root.lookupType('FirstContact')
        );
    }

    static getPlayerInfos(playerIds) {
        const tasks = playerIds.map(playerId => this.getPlayerInfo(playerId));
        return Promise.all(tasks);
    }
}

module.exports = EggIncApi;
