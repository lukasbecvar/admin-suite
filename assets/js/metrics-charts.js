/** metrics component graph charts functionality */
const ApexCharts = require('apexcharts')

// overload color highlight function
function getColor(percentage) {
    return percentage > 80 ? '#f73925' : '#19bf3f'
}
function getBorderColor(percentage) {
    return percentage > 80 ? '#f73925' : '#19bf3f'
}

// CPU radial graph
var optionsCpuRadial = {
    series: [window.metricsData.cpu.current],
    chart: {
        type: 'radialBar',
        height: '100%',
        width: '100%',
        background: '#1d1d1d',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
        },
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%',
            },
            track: {
                background: '#636363',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            },
        },
    },
    labels: ['CPU Usage'],
    colors: [getColor(window.metricsData.cpu.current)],
    theme: {
        mode: 'dark',
    },
    legend: {
        show: false,
    },
}
var chartCpuRadial = new ApexCharts(document.querySelector("#cpu-usage-radial"), optionsCpuRadial)
chartCpuRadial.render()

// CPU area graph (historical data)
var optionsCpuLine = {
    series: [{
        name: 'CPU Usage',
        data: window.metricsData.cpu.data
    }],
    chart: {
        type: 'area',
        height: 250,
        background: '#1d1d1d',
        toolbar: {
            show: true,
            tools: {
                zoomin: false,
                zoomout: false,
                reset: true,
                pan: true,
                selection: true,
            }
        }
    },
    stroke: {
        curve: 'straight',
        width: 3,
        colors: ['#009900']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    colors: ['#28a745'],
    theme: {
        mode: 'dark',
    },
    noData: {
        text: 'No Data Available',
        align: 'center',
        verticalAlign: 'middle',
        style: {
            color: '#fff',
            fontSize: '16px',
            fontFamily: 'Arial'
        }
    }
}
var chartCpuLine = new ApexCharts(document.querySelector("#cpu-usage-line"), optionsCpuLine)
chartCpuLine.render()

// RAM radial graph
var optionsRamRadial = {
    series: [window.metricsData.ram.current],
    chart: {
        type: 'radialBar',
        height: '100%',
        width: '100%',
        background: '#1d1d1d',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
        },
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%',
            },
            track: {
                background: '#636363',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            },
        },
    },
    labels: ['RAM Usage'],
    colors: [getColor(window.metricsData.ram.current)],
    theme: {
        mode: 'dark',
    },
    legend: {
        show: false,
    },
}
var chartRamRadial = new ApexCharts(document.querySelector("#ram-usage-radial"), optionsRamRadial)
chartRamRadial.render()

// RAM area graph (historical data)
var optionsRamLine = {
    series: [{
        name: 'RAM Usage',
        data: window.metricsData.ram.data
    }],
    chart: {
        type: 'area',
        height: 250,
        background: '#1d1d1d',
        toolbar: {
            show: true,
            tools: {
                zoomin: false,
                zoomout: false,
                reset: true,
                pan: true,
                selection: true,
            }
        }
    },
    stroke: {
        curve: 'straight',
        width: 3,
        colors: ['#1abc9c']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    colors: ['#20c997'],
    theme: {
        mode: 'dark',
    },
    noData: {
        text: 'No Data Available',
        align: 'center',
        verticalAlign: 'middle',
        style: {
            color: '#fff',
            fontSize: '16px',
            fontFamily: 'Arial'
        }
    }
}
var chartRamLine = new ApexCharts(document.querySelector("#ram-usage-line"), optionsRamLine)
chartRamLine.render()

// Storage radial graph
var optionsStorageRadial = {
    series: [window.metricsData.storage.current],
    chart: {
        type: 'radialBar',
        height: '100%',
        width: '100%',
        background: '#1d1d1d',
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
        },
    },
    plotOptions: {
        radialBar: {
            offsetY: -25,
            hollow: {
                size: '60%',
            },
            track: {
                background: '#636363',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.cpu.current)]
            },
        },
    },
    labels: ['Storage Usage'],
    colors: [getColor(window.metricsData.storage.current)],
    theme: {
        mode: 'dark',
    },
    legend: {
        show: false,
    },
}
var chartStorageRadial = new ApexCharts(document.querySelector("#storage-usage-radial"), optionsStorageRadial)
chartStorageRadial.render()

// Storage area graph (historical data)
var optionsStorageLine = {
    series: [{
        name: 'Storage Usage',
        data: window.metricsData.storage.data
    }],
    chart: {
        type: 'area',
        height: 250,
        background: '#1d1d1d',
        toolbar: {
            show: true,
            tools: {
                zoomin: false,
                zoomout: false,
                reset: true,
                pan: true,
                selection: true,
            }
        }
    },
    stroke: {
        curve: 'straight',
        width: 3,
        colors: ['#0056b3']
    },
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: window.metricsData.categories,
        tickAmount: Math.floor(window.metricsData.categories.length / 2)
    },
    colors: ['#007bff'],
    theme: {
        mode: 'dark',
    },
    noData: {
        text: 'No Data Available',
        align: 'center',
        verticalAlign: 'middle',
        style: {
            color: '#fff',
            fontSize: '16px',
            fontFamily: 'Arial'
        }
    }
}
var chartStorageLine = new ApexCharts(document.querySelector("#storage-usage-line"), optionsStorageLine)
chartStorageLine.render()
