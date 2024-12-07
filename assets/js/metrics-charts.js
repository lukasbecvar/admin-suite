/** metrics component graph charts functionality */
const ApexCharts = require('apexcharts')

// overload color highlight functions
function getColor(percentage) {
    return percentage > 80 ? '#f73925' : '#19bf3f'
}
function getBorderColor(percentage) {
    return percentage > 80 ? '#f73925' : '#19bf3f'
}

// cpu usage radial graph
var optionsCpuRadial = {
    series: [window.metricsData.cpu.current],
    chart: {
        width: '100%',
        height: '100%',
        type: 'radialBar',
        background: '#151718',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        }
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%'
            },
            track: {
                strokeWidth: '100%',
                background: '#636363'
            },
            dataLabels: {
                show: false
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            }
        }
    },
    labels: ['CPU Usage'],
    colors: [getColor(window.metricsData.cpu.current)],
    theme: {
        mode: 'dark'
    },
    legend: {
        show: false
    }
}
var chartCpuRadial = new ApexCharts(document.querySelector("#cpu-usage-radial"), optionsCpuRadial)
chartCpuRadial.render()

// cpu usage area graph (historical data)
var optionsCpuLine = {
    series: [{
        name: 'CPU Usage',
        data: window.metricsData.cpu.data
    }],
    chart: {
        height: 250,
        type: 'area',
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
    stroke: {
        width: 3,
        curve: 'straight',
        colors: ['#009900']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    yaxis: {
        min: 0,
        max: 100,
        labels: {
            formatter: function (value) {
                return value + '%'
            }
        }
    },
    colors: ['#28a745'],
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
            fontFamily: 'Arial'
        }
    }
}
var chartCpuLine = new ApexCharts(document.querySelector("#cpu-usage-line"), optionsCpuLine)
chartCpuLine.render()

// ram usage radial graph
var optionsRamRadial = {
    series: [window.metricsData.ram.current],
    chart: {
        width: '100%',
        height: '100%',
        type: 'radialBar',
        background: '#151718',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        }
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%'
            },
            track: {
                strokeWidth: '100%',
                background: '#636363'
            },
            dataLabels: {
                show: false
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            }
        }
    },
    labels: ['RAM Usage'],
    colors: [getColor(window.metricsData.ram.current)],
    theme: {
        mode: 'dark'
    },
    legend: {
        show: false
    }
}
var chartRamRadial = new ApexCharts(document.querySelector("#ram-usage-radial"), optionsRamRadial)
chartRamRadial.render()

// ram usage area graph (historical data)
var optionsRamLine = {
    series: [{
        name: 'RAM Usage',
        data: window.metricsData.ram.data
    }],
    chart: {
        height: 250,
        type: 'area',
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
    stroke: {
        width: 3,
        curve: 'straight',
        colors: ['#1abc9c']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    yaxis: {
        min: 0,
        max: 100,
        labels: {
            formatter: function (value) {
                return value + '%'
            }
        }
    },
    colors: ['#20c997'],
    theme: {
        mode: 'dark'
    },
    noData: {
        align: 'center',
        verticalAlign: 'middle',
        text: 'No Data Available',
        style: {
            color: '#fff',
            fontSize: '16px',
            fontFamily: 'Arial'
        }
    }
}
var chartRamLine = new ApexCharts(document.querySelector("#ram-usage-line"), optionsRamLine)
chartRamLine.render()

// storage usage radial graph
var optionsStorageRadial = {
    series: [window.metricsData.storage.current],
    chart: {
        width: '100%',
        height: '100%',
        type: 'radialBar',
        background: '#151718',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        }
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%'
            },
            track: {
                strokeWidth: '100%',
                background: '#636363'
            },
            dataLabels: {
                show: false
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            }
        }
    },
    labels: ['Storage Usage'],
    colors: [getColor(window.metricsData.storage.current)],
    theme: {
        mode: 'dark'
    },
    legend: {
        show: false
    },
}
var chartStorageRadial = new ApexCharts(document.querySelector("#storage-usage-radial"), optionsStorageRadial)
chartStorageRadial.render()

// storage usage area graph (historical data)
var optionsStorageLine = {
    series: [{
        name: 'Storage Usage',
        data: window.metricsData.storage.data
    }],
    chart: {
        height: 250,
        type: 'area',
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
    stroke: {
        width: 3,
        curve: 'straight',
        colors: ['#0056b3']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    yaxis: {
        min: 0,
        max: 100,
        labels: {
            formatter: function (value) {
                return value + '%'
            }
        }
    },
    colors: ['#007bff'],
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
            fontFamily: 'Arial'
        }
    }
}
var chartStorageLine = new ApexCharts(document.querySelector("#storage-usage-line"), optionsStorageLine)
chartStorageLine.render()
