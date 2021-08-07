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

            let colors = ['#d14e40', '#5b6cf7', '#68f948']
            let labels = {}
            let dataSet = {}
            this.dataPoints.forEach((dataPoint, key) => {
                dataSet[dataPoint.dataKey] = {
                    label: dataPoint.label,
                    data: {},
                    fill: false,
                    borderColor: colors[key],
                }
            })

            _.chain(this.userStats)
                .sortBy(value => new Date(value.record_time))
                .forEach(value => {
                    let recordDate = new Date(value.record_time)
                    let key = (recordDate.getMonth() + 1) + '/' + recordDate.getDate() + '/' + recordDate.getFullYear()

                    this.dataPoints.forEach(dataPoint => {
                        labels[key] = true
                        dataSet[dataPoint.dataKey].data[key] = value[dataPoint.dataKey]
                    })
                })
                .value()

            this.dataPoints.forEach(dataPoint => {
                dataSet[dataPoint.dataKey].data = _.values(dataSet[dataPoint.dataKey].data)
            })

            let data = {
                labels: _.keys(labels),
                datasets: _.values(dataSet),
            }

            return {
                dataSet: data,
                options: options,
            }
        },
        props: ['userStats', 'dataPoints', 'commaFormat', 'eggFormat', 'showRole',],
    }
</script>
