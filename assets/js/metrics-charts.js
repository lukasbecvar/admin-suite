/** metrics component graph functionality */
const ApexCharts = require('apexcharts')

document.addEventListener('DOMContentLoaded', () => {
    // function for dynamic color based on value
    function getColor(value, metricData) {
        const maxMetricValue = Math.max(...metricData.map(m => m.value))
        if (maxMetricValue > 100) {
            return '#19bf3f'
        }
        return value > 80 ? '#f73925' : '#19bf3f'
    }

    if (!window.metricsData) {
        console.error('Metrics data not found in window.metricsData')
        return
    }
    const services = typeof window.metricsData.categories === 'undefined' ? window.metricsData : { default: window.metricsData }

    // iterate over each service in metrics data 
    Object.keys(services).forEach(serviceName => {
        const { categories, metrics, percentage } = services[serviceName]
        Object.keys(metrics).forEach(metricName => {
            const metricData = metrics[metricName]
            const elementId = `${metricName}-${serviceName}`.replace(/\./g, '_').replace(/ /g, '_')
            const element = document.querySelector(`#${elementId}-line`)

            // check if chart element exists
            if (!element) {
                console.error(`Element #${elementId}-line not found`)
                return
            }

            // determine dynamic color based on the latest metric value
            const dynamicColor = getColor(metricData[metricData.length - 1]?.value || 0, metricData)

            const options = {
                chart: {
                    type: 'area',
                    height: 250,
                    background: '#151718',
                    foreColor: '#ccc',
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
                    curve: 'smooth',
                    colors: [dynamicColor]
                },
                markers: {
                    size: 4,
                    colors: ['#ffffff'],
                    strokeColors: [dynamicColor],
                    strokeWidth: 2,
                    hover: {
                        size: 7,
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.5,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.1)',
                },
                xaxis: {
                    categories: categories,
                    tickAmount: Math.floor(categories.length / 2),
                    labels: {
                        style: {
                            colors: '#ccc',
                            fontSize: '12px',
                            fontFamily: 'Roboto, sans-serif'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: metricName.replace(/_/g, ' ').toUpperCase(),
                        style: {
                            color: '#ccc',
                            fontFamily: 'Roboto, sans-serif'
                        }
                    },
                    labels: {
                        style: {
                            colors: '#ccc',
                            fontSize: '12px',
                            fontFamily: 'Roboto, sans-serif'
                        }
                    },
                    forceNiceScale: false
                },
                colors: [dynamicColor],
                theme: {
                    mode: 'dark',
                },
                tooltip: {
                    theme: 'dark',
                    x: {
                        format: 'dd MMM'
                    }
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
                dataLabels: {
                    enabled: false
                }
            }

            // format chart for percentage data if needed
            if (percentage) {
                options.yaxis.min = 0
                options.yaxis.max = 100
                options.yaxis.labels.formatter = function (value) {
                    return value + '%'
                }
            }

            const chart = new ApexCharts(element, options)
            chart.render()
        })
    })
})
