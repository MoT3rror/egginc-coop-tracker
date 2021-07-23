<template>
    <line-chart :chart-data="dataSet" :height="300" :width="300" :options="{maintainAspectRatio:false}"></line-chart>
</template>

<script>
    import LineChart from './LineChart'
    import _ from 'lodash'

    export default {
        components: {LineChart},
        data() {
            let dataSet = {}

            let test = _.chain(this.userStats)
                .sortBy(value => new Date(value.record_time))
                .forEach(value => {
                    let recordDate = new Date(value.record_time)
                    let key = (recordDate.getMonth() + 1) + '/' + recordDate.getDate() + '/' + recordDate.getFullYear()

                    dataSet[key] = value[this.dataKey]
                })
                .value()

            let data = {
                labels: [_.keys(dataSet)],
                datasets: [
                    {
                        label: this.label,
                        data: _.values(dataSet),
                    }
                ]
            }
            return {dataSet: data}
        },
        props: ['userStats', 'label', 'dataKey'],
    }
</script>
