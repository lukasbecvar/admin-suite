/** system resources dashboard refresher functionality */
document.addEventListener('DOMContentLoaded', function() {
    let firstRequest = true

    // get progress bars
    const cpuProgress = document.getElementById('cpu-progress')
    const ramProgress = document.getElementById('ram-progress')
    const networkProgress = document.getElementById('network-progress')
    const driveProgress = document.getElementById('drive-space-progress')

    // get metrics current usages elements
    const cpuBarElement = document.querySelector('.cpu-bar')
    const ramBarElement = document.querySelector('.ram-bar')
    const storageBarElement = document.querySelector('.storage-bar')
    const cpuPercentageElement = document.querySelector('.cpu-percentage')
    const ramPercentageElement = document.querySelector('.ram-percentage')
    const storagePercentageElement = document.querySelector('.storage-percentage')

    // get info elements
    const cpuUsageElement = document.getElementById('cpu-usage')
    const ramUsageElement = document.getElementById('ram-usage')
    const systemStorageElement = document.getElementById('drive-space')
    const systemUptimeElement = document.getElementById('system-uptime')
    const networkUsageElement = document.getElementById('network-usage')
    const networkUsagePingElement = document.getElementById('network-usage-ping')
    const networkUsageUploadElement = document.getElementById('network-usage-upload')
    const networkUsageDownloadElement = document.getElementById('network-usage-download')
    const networkLastCheckTimeElement = document.getElementById('network-last-check-time')
    const networkUsageInterfaceElement = document.getElementById('network-usage-interface')
    const networkUsagePingServerElement = document.getElementById('network-usage-ping-server')

    // get network stats elements for switching visibility
    const loadingNetworkStats = document.getElementById('loading-network-stats')
    const networkStats = document.getElementById('network-stats')

    function updateResourcesUsage() {
        // show loading only on first request
        if (firstRequest) {
            networkStats.style.display = 'none'
            loadingNetworkStats.style.display = 'block'
        }

        // get resources usage from API
        fetch('/api/system/resources').then(response => response.json()).then(data => {
            // update resources usage
            systemUptimeElement.innerHTML = data.hostUptime
            cpuUsageElement.innerHTML = 'CPU: ' + data.diagnosticData.cpuUsage + '%'
            ramUsageElement.innerHTML = 'RAM: (' + data.ramUsage.used + 'G / ' + data.diagnosticData.ramUsage + '%)'
            systemStorageElement.innerHTML = 'STORAGE: (' + data.storageUsage + 'G / ' + data.diagnosticData.driveSpace + '%)'

            // update network usage
            networkUsageElement.innerHTML = data.networkStats.networkUsagePercent + '%'
            networkUsageDownloadElement.innerHTML = data.networkStats.downloadMbps + 'M/s'
            networkUsageUploadElement.innerHTML = data.networkStats.uploadMbps + 'M/s'
            networkUsagePingElement.innerHTML = data.networkStats.pingMs + 'ms'
            networkUsageInterfaceElement.innerHTML = data.networkStats.interface
            networkUsagePingServerElement.innerHTML = data.networkStats.pingToIp
            networkLastCheckTimeElement.innerHTML = data.networkStats.lastCheckTime

            // update progress bars
            cpuProgress.style.width = data.diagnosticData.cpuUsage + '%'
            ramProgress.style.width = data.diagnosticData.ramUsage + '%'
            driveProgress.style.width = data.diagnosticData.driveSpace + '%'
            networkProgress.style.width = data.networkStats.networkUsagePercent + '%'

            // update progress bars background color
            cpuProgress.style.background = data.diagnosticData.cpuUsage > 80
                ? 'linear-gradient(45deg, #ef4444, #f87171)'
                : 'linear-gradient(45deg, #3b82f6, #60a5fa)'
            ramProgress.style.background = data.diagnosticData.ramUsage > 80
                ? 'linear-gradient(45deg, #ef4444, #f87171)'
                : 'linear-gradient(45deg, #10b981, #34d399)'
            driveProgress.style.background = data.diagnosticData.driveSpace > 80
                ? 'linear-gradient(45deg, #ef4444, #f87171)'
                : 'linear-gradient(45deg, #8b5cf6, #a78bfa)'
            networkProgress.style.background = data.networkStats.networkUsagePercent > 80
                ? 'linear-gradient(45deg, #ef4444, #f87171)'
                : 'linear-gradient(45deg, #06b6d4, #3b82f6)'

            // update metrics current usages if elements exist
            if (cpuPercentageElement) {
                cpuPercentageElement.textContent = data.diagnosticData.cpuUsage + '%'
            }
            if (ramPercentageElement) {
                ramPercentageElement.textContent = data.diagnosticData.ramUsage + '%'
            }
            if (storagePercentageElement) {
                storagePercentageElement.textContent = data.diagnosticData.driveSpace + '%'
            }
            if (cpuBarElement) {
                cpuBarElement.style.width = data.diagnosticData.cpuUsage + '%'
            }
            if (ramBarElement) {
                ramBarElement.style.width = data.diagnosticData.ramUsage + '%'
            }
            if (storageBarElement) {
                storageBarElement.style.width = data.diagnosticData.driveSpace + '%'
            }

            // load stats only on first request
            if (firstRequest) {
                loadingNetworkStats.style.display = 'none'
                networkStats.style.display = 'block'
                firstRequest = false
            }
        }).catch(error => {
            console.log(error)
        })
    }

    setInterval(updateResourcesUsage, 10000)
    updateResourcesUsage()
})
