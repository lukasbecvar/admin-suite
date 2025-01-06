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

    const { categories, metrics } = window.metricsData

    Object.keys(metrics).forEach(metricName => {
        const metricData = metrics[metricName]
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

        const chart = new ApexCharts(
            document.querySelector(`#${metricName}-line`),
            options
        )

        chart.render()
    })
})
