<template>
    <span>
        {{ humanReadable }}
    </span>
</template>
<script>
    import EggFormatter from '../Helpers/EggFormatter'

    export default {
        props: {
            eggs: Number
        },
        computed: {
            humanReadable() {
                return (new EggFormatter).format(this.eggs)
            }
        }
    }
</script>
