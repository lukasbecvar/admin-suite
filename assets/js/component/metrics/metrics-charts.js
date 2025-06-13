/** metrics component graph functionality */
const ApexCharts = require('apexcharts')

document.addEventListener('DOMContentLoaded', () => {
    // function for dynamic color based on value
    function getColor(value, metricData) {
        const maxMetricValue = Math.max(...metricData.map(m => m.value))
        if (maxMetricValue > 100) {
            return '#1fa33d'
        }
        return value > 50 ? '#cc3829' : '#1fa33d'
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

            // chart style options
            const options = {
                chart: {
                    type: 'area',
                    height: 250,
                    background: 'transparent',
                    foreColor: '#9ca3af',
                    toolbar: {
                        show: true,
                        offsetX: -10,
                        offsetY: 0,
                        tools: {
                            download: false,
                            selection: false,
                            zoom: false,
                            zoomin: true,
                            zoomout: true,
                            pan: false,
                            reset: true
                        }
                    },
                    zoom: {
                        enabled: true,
                        type: 'x',
                        autoScaleYaxis: true,
                        allowMouseWheelZoom: false,
                        zoomedArea: {
                            fill: {
                                color: '#60a5fa',
                                opacity: 0.1
                            },
                            stroke: {
                                color: '#60a5fa',
                                opacity: 0.6,
                                width: 1
                            }
                        }
                    },
                    selection: {
                        enabled: true,
                        type: 'x',
                        fill: {
                            color: '#60a5fa',
                            opacity: 0.1
                        },
                        stroke: {
                            width: 1,
                            color: '#60a5fa',
                            opacity: 0.6,
                            dashArray: 3
                        }
                    },
                    events: {
                        selection: function(chartContext, { xaxis, yaxis }) {
                            // automatically zoom to selected area
                            if (xaxis && xaxis.min !== undefined && xaxis.max !== undefined) {
                                chartContext.updateOptions({
                                    xaxis: {
                                        min: xaxis.min,
                                        max: xaxis.max
                                    }
                                }, false, true, false)
                            }
                        }
                    },
                    margin: {
                        top: 10,
                        right: 10,
                        bottom: 10,
                        left: 10
                    },
                    offsetY: 0,
                    offsetX: 0
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
                    borderColor: 'rgba(156, 163, 175, 0.2)',
                    strokeDashArray: 2,
                },
                xaxis: {
                    categories: categories,
                    tickAmount: Math.floor(categories.length / 2),
                    labels: {
                        style: {
                            colors: '#9ca3af',
                            fontSize: '11px',
                            fontFamily: 'Inter, system-ui, sans-serif'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: metricName.replace(/_/g, ' ').toUpperCase(),
                        style: {
                            color: '#9ca3af',
                            fontSize: '12px',
                            fontFamily: 'Inter, system-ui, sans-serif'
                        }
                    },
                    labels: {
                        style: {
                            colors: '#9ca3af',
                            fontSize: '11px',
                            fontFamily: 'Inter, system-ui, sans-serif'
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
                        color: '#d1d5db',
                        fontSize: '14px',
                        fontFamily: 'Inter, system-ui, sans-serif',
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
            chart.render().then(() => {
                // force remove any bottom spacing after render
                const chartElement = element.querySelector('.apexcharts-canvas')
                if (chartElement) {
                    chartElement.style.marginBottom = '0px'
                    chartElement.style.paddingBottom = '0px'
                    chartElement.style.display = 'block'
                    chartElement.style.verticalAlign = 'top'
                }

                // remove spacing from parent container
                element.style.marginBottom = '0px'
                element.style.paddingBottom = '0px'
                element.style.lineHeight = '0'
            })
        })
    })
})
