<template>
    <line-chart :chart-data="dataSet" :height="300" :width="300" :options="options"></line-chart>
</template>

<script>
    import LineChart from './LineChart'
    import _ from 'lodash'
    import EggFormatter from '../Helpers/EggFormatter'

    export default {
        components: {LineChart},
        data() {
            let options = {
                maintainAspectRatio: false,
                scales: {
                    yAxes: [
                        {
                            ticks: {
                                precision: 0
                            }
                        }
                    ]
                }
            }

            if (this.eggFormat) {
                options.tooltips = {
                    callbacks: {
                        label: toolTipItem => {
                            return (new EggFormatter).format(toolTipItem.yLabel) + ' - ' + (this.showRole ? (new EggFormatter).role(toolTipItem.yLabel) : '')
                        }
                    }
                }
                options.scales = {
                    yAxes: [
                        {
                            type: 'logarithmic',
                            ticks: {
                                callback: value => {
                                    return ((new EggFormatter).format(value))
                                }
                            }
                        }
                    ],
                }
            }

            if (this.commaFormat) {
                options.tooltips = {
                    callbacks: {
                        label: toolTipItem => {
                            return parseInt(toolTipItem.yLabel).toLocaleString(undefined, {minimumFractionDigits: 0})
                        }
                    }
                }
                options.scales = {
                    yAxes: [
                        {
                            type: 'logarithmic',
                            ticks: {
                                callback: value => {
                                    return parseInt(value).toLocaleString(undefined, {minimumFractionDigits: 0})
                                }
                            }
                        }
                    ],
                }
            }

            let dataSet = {}

            _.chain(this.userStats)
                .sortBy(value => new Date(value.record_time))
                .forEach(value => {
                    let recordDate = new Date(value.record_time)
                    let key = (recordDate.getMonth() + 1) + '/' + recordDate.getDate() + '/' + recordDate.getFullYear()

                    dataSet[key] = value[this.dataKey]
                })
                .value()

            let data = {
                labels: _.keys(dataSet),
                datasets: [
                    {
                        label: this.label,
                        data: _.values(dataSet),
                    }
                ]
            }
            return {
                dataSet: data,
                options: options,
            }
        },
        props: ['userStats', 'label', 'dataKey', 'eggFormat', 'showRole', 'commaFormat'],
    }
</script>
