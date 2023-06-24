const b = require('base64-arraybuffer')
const axios = require('axios')
const protobuf = require("protobufjs");
const pako = require('pako');
const root = protobuf.loadSync('js/Proto/egginc.proto');

// default values and consts
const EI_USER_ID = 'EI6411720689451008';
const CLIENT_VERSION = 50
const APP_VERSION = '1.27.0';

const ei_request = (path, payload, requestPB, responsePB) => {
    return new Promise((resolve, reject) => {
        var errMsg = requestPB.verify(payload);
        if (errMsg)
            throw Error(errMsg)

        var buffer = requestPB.encode(requestPB.create(payload)).finish()

        let options = {
            url: `https://ctx-dot-auxbrainhome.appspot.com/${path}`,
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
    static getCoopStatus(contract, coop, clientVersion, appVersion) {
        const payload = {
            contractIdentifier: contract,
            coopIdentifier: coop,
            userId: EI_USER_ID,
            rinfo: {
                eiUserId: EI_USER_ID,
                clientVersion: parseInt(clientVersion) || CLIENT_VERSION,
                version: appVersion || APP_VERSION,
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

    static getCoopQuery(contract, coop, clientVersion, appVersion) {
        const payload = {
            contractIdentifier: contract,
            coopIdentifier: coop,
            userId: EI_USER_ID,
            league: 4,
            grade: 4,
            clientVersion: parseInt(clientVersion) || CLIENT_VERSION,
            rinfo: {
                eiUserId: EI_USER_ID,
                clientVersion: parseInt(clientVersion) || CLIENT_VERSION,
                version: appVersion || APP_VERSION,
                platform: 'ANDROID',
            }
        }

        return ei_request(
            'ei/query_coop',
            payload,
            root.lookupType('QueryCoopRequest'),
            root.lookupType('QueryCoopResponse')
        );
    }

    static getPeriodicals(clientVersion, appVersion) {
        const payload = {
            userId: EI_USER_ID,
            currentClientVersion: parseInt(clientVersion) || CLIENT_VERSION,
            rinfo: {
                eiUserId: EI_USER_ID,
                clientVersion: parseInt(clientVersion) || CLIENT_VERSION,
                version: appVersion || APP_VERSION,
                platform: 'ANDROID',
            }
        }

        return ei_request(
            'ei/get_periodicals',
            payload,
            root.lookupType('GetPeriodicalsRequest'),
            root.lookupType('AuthenticatedMessage')
        ).then(data => {
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

    static getCurrentVersion() {
        return {
            currentClientVersion: CLIENT_VERSION,
            appVersion: APP_VERSION,
        }
    }
}

module.exports = EggIncApi;
