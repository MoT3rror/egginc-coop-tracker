<template>
    <span>
        {{ humanReadable }}
    </span>
</template>
<script>
    import EggFormatter from '../Helpers/EggFormatter'

    export default {
        props: {
            soulPower: Number
        },
        computed: {
            humanReadable() {
                console.log(Math.pow(10, this.soulPower) * 100)
                return (new EggFormatter).role(Math.pow(10, this.soulPower) * 100)
            }
        }
    }
</script>
