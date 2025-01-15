/** metrics component graph functionality */
const ApexCharts = require('apexcharts')

document.addEventListener("DOMContentLoaded", () => {
    // function for overload color highlight
    function getColor(value, metricData) {
        const maxMetricValue = Math.max(...metricData.map(m => m.value))
        if (maxMetricValue > 100) { 
            return '#19bf3f'
        }
        return value > 80 ? '#f73925' : '#19bf3f'
    }

    if (!window.metricsData) {
        console.error("Metrics data not found in window.metricsData")
        return
    }
    const services = typeof window.metricsData.categories === 'undefined' ? window.metricsData : { default: window.metricsData }

    // iterate over each service in metricsData
    Object.keys(services).forEach(serviceName => {
        const { categories, metrics, percentage } = services[serviceName]
        Object.keys(metrics).forEach(metricName => {
            const metricData = metrics[metricName]
            const elementId = `${metricName}-${serviceName}`.replace(/\./g, '_').replace(/ /g, '_')
            const element = document.querySelector(`#${elementId}-line`)

            // check if chart element found
            if (!element) {
                console.error(`Element #${elementId}-line not found`)
                return
            }

            const options = {
                chart: {
                    type: 'area',
                    height: 250,
                    background: '#151718',
                    toolbar: {
                        show: true,
                        tools: {
                            pan: false,
                            reset: true,
                            zoomin: false,
                            zoomout: false,
                            selection: true
                        }
                    }
                },
                series: [{
                    name: metricName.replace(/_/g, ' ').toUpperCase(),
                    data: metricData.map(m => m.value),
                }],
                stroke: {
                    width: 3,
                    curve: 'straight',
                    colors: [getColor(metricData[metricData.length - 1]?.value || 0, metricData)]
                },
                xaxis: {
                    categories: categories,
                    tickAmount: Math.floor(categories.length / 2)
                },
                yaxis: {
                    title: {
                        text: metricName.replace(/_/g, ' ').toUpperCase(),
                    },
                    forceNiceScale: false
                },
                colors: [getColor(metricData[metricData.length - 1]?.value || 0, metricData)],
                theme: {
                    mode: 'dark',
                },
                noData: {
                    align: 'center',
                    verticalAlign: 'middle',
                    text: 'No Data Available',
                    style: {
                        color: '#fff',
                        fontSize: '16px',
                        fontFamily: 'Arial',
                    }
                },
            }

            // format data to percentage chart
            if (percentage) {
                options.yaxis.min = 0
                options.yaxis.max = 100
                options.yaxis.labels = {
                    formatter: function (value) {
                        return value + '%'
                    }
                }
            }

            const chart = new ApexCharts(element, options)
            chart.render()
        })
    })
})
