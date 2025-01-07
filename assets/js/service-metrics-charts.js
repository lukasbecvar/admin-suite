/** metrics component graph functionality for service metrics */
const ApexCharts = require('apexcharts')

function getColor(value) {
    return value > 80 ? '#f73925' : '#19bf3f'
}

document.addEventListener("DOMContentLoaded", () => {
    if (!window.metricsData) {
        console.error("Metrics data not found in window.metricsData")
        return
    }
    const services = typeof window.metricsData.categories === 'undefined' 
        ? window.metricsData 
        : { default: window.metricsData }

    // iterate over each service in metricsData
    Object.keys(services).forEach(serviceName => {
        const { categories, metrics } = services[serviceName]

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
                    colors: [getColor(metricData[metricData.length - 1]?.value || 0)]
                },
                xaxis: {
                    categories: categories,
                    tickAmount: Math.floor(categories.length / 2)
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    labels: {
                        formatter: function (value) {
                            return value + '%'
                        }
                    },
                    title: {
                        text: metricName.replace(/_/g, ' ').toUpperCase(),
                    },
                },
                colors: [getColor(metricData[metricData.length - 1]?.value || 0)],
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

            const chart = new ApexCharts(element, options)
            chart.render()
        })
    })
})
