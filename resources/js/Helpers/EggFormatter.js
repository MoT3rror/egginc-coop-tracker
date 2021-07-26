import MagnitudeFormat from '../magnitudeFormat.json'

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

    format(number) {
        let magnitude = this.magnitudeGet(number)
        if (!magnitude) {
            return number
        }

        return Math.round(number / Math.pow(10, magnitude.magnitude) * 1000) / 1000 + magnitude.symbol
    }
}
