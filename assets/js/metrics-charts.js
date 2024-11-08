/** metrics component graph charts functionality */
const ApexCharts = require('apexcharts');

// overload color highlight function
function getColor(percentage) {
    return percentage > 80 ? '#e74c3c' : '#28a745';
}
function getBorderColor(percentage) {
    return percentage > 80 ? '#c0392b' : '#27ae60';
}

// CPU radial graph
var optionsCpuRadial = {
    series: [window.metricsData.cpu.current],
    chart: {
        type: 'radialBar',
        height: '250px',
        background: '#1d1d1d',
    },
    plotOptions: {
        radialBar: {
            hollow: {
                size: '50%',
            },
            track: {
                background: '#555555',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            startAngle: -90,
            endAngle: 90,
            strokeWidth: 20,
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
};
var chartCpuRadial = new ApexCharts(document.querySelector("#cpu-usage-radial"), optionsCpuRadial);
chartCpuRadial.render();

// CPU line graph (historical data)
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
    },
    colors: ['#28a745'],
    theme: {
        mode: 'dark',
    },
};
var chartCpuLine = new ApexCharts(document.querySelector("#cpu-usage-line"), optionsCpuLine);
chartCpuLine.render();

// RAM radial graph
var optionsRamRadial = {
    series: [window.metricsData.ram.current],
    chart: {
        type: 'radialBar',
        height: '250px',
        background: '#1d1d1d',
    },
    plotOptions: {
        radialBar: {
            hollow: {
                size: '50%',
            },
            track: {
                background: '#555555',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            startAngle: -90,
            endAngle: 90,
            strokeWidth: 20,
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.ram.current)]
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
};
var chartRamRadial = new ApexCharts(document.querySelector("#ram-usage-radial"), optionsRamRadial);
chartRamRadial.render();

// RAM line graph (historical data)
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
    },
    colors: ['#20c997'],
    theme: {
        mode: 'dark',
    },
};
var chartRamLine = new ApexCharts(document.querySelector("#ram-usage-line"), optionsRamLine);
chartRamLine.render();

// Storage radial graph
var optionsStorageRadial = {
    series: [window.metricsData.storage.current],
    chart: {
        type: 'radialBar',
        height: '250px',
        background: '#1d1d1d',
    },
    plotOptions: {
        radialBar: {
            hollow: {
                size: '50%',
            },
            track: {
                background: '#555555',
                strokeWidth: '100%',
            },
            dataLabels: {
                show: false,
            },
            startAngle: -90,
            endAngle: 90,
            strokeWidth: 20,
            stroke: {
                width: 8,
                colors: [getBorderColor(window.metricsData.storage.current)]
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
};
var chartStorageRadial = new ApexCharts(document.querySelector("#storage-usage-radial"), optionsStorageRadial);
chartStorageRadial.render();

// Storage line graph (historical data)
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
    },
    colors: ['#007bff'],
    theme: {
        mode: 'dark',
    },
};
var chartStorageLine = new ApexCharts(document.querySelector("#storage-usage-line"), optionsStorageLine);
chartStorageLine.render();
