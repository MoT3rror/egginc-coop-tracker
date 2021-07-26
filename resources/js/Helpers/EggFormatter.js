import MagnitudeFormat from '../magnitudeFormat.json'
import RoleMagnitudeFormat from '../roleMagnitude.json'

export default class {
    magnitudeGet(number) {
        let last = null;
        for (let i = 0; i < MagnitudeFormat.length; i++) {
            if (number / Math.pow(10, MagnitudeFormat[i].magnitude) < 1) {
                break;
            }
            last = MagnitudeFormat[i]
        }
        return last
    }

    roleMagnitudeGet(number) {
        let last = null;
        for (let i = 0; i < RoleMagnitudeFormat.length; i++) {
            if (number / Math.pow(10, RoleMagnitudeFormat[i].magnitude) < 1) {
                break;
            }
            last = RoleMagnitudeFormat[i]
        }
        return last
    }

    format(number) {
        let magnitude = this.magnitudeGet(number)
        if (!magnitude) {
            return number
        }

        return Math.round(number / Math.pow(10, magnitude.magnitude) * 1000) / 1000 + magnitude.symbol
    }

    role(number) {
        let magnitude = this.roleMagnitudeGet(number)
        if (!magnitude) {
            return ''
        }

        return magnitude.name
    }
}
