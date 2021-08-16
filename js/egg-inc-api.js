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

            resolve(responsePB.decode(byteArray))
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
            currentClientVersion: 34,
            rinfo: {
                eiUserId: 'EI6411720689451008',
                clientVersion: 34,
                version: '1.21.2',
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
            clientVersion: 34,
            platform: 2,
            eiUserId: playerId,
            username: '',
            rinfo: {
                eiUserId: playerId,
                clientVersion: 34,
                version: '1.21.2',
                platform: 'ANDROID'
            }
        }

        return ei_request(
            'ei/first_contact',
            payload,
            root.lookupType('FirstContactRequestPayload'),
            root.lookupType('FirstContactResponsePayload')
        );
    }

    static getPlayerInfos(playerIds) {
        const tasks = playerIds.map(playerId => this.getPlayerInfo(playerId));
        return Promise.all(tasks);
    }
}

module.exports = EggIncApi;
