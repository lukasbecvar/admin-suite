/** system resources dashboard refresher functionality */
document.addEventListener('DOMContentLoaded', function()
{
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

    // format bytes to human readable format
    function formatBytes(mbps, decimals = 2) {
        const numericMbps = Number(mbps)
        if (!Number.isFinite(numericMbps) || numericMbps <= 0) {
            return '0 B/s'
        }
        const bytesPerSecond = numericMbps * 125000
        const k = 1024
        const dm = decimals < 0 ? 0 : decimals
        const sizes = ['B/s', 'KB/s', 'MB/s', 'GB/s', 'TB/s', 'PB/s', 'EB/s', 'ZB/s', 'YB/s']
        const i = Math.floor(Math.log(bytesPerSecond) / Math.log(k))
        return parseFloat((bytesPerSecond / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
    }

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
            const usagePercent = Math.min(
                100,
                Math.max(0, Number(data.networkStats.networkUsagePercent) || 0)
            )
            networkUsageElement.innerHTML = usagePercent.toFixed(2) + '%'
            networkUsageDownloadElement.innerHTML = formatBytes(data.networkStats.downloadMbps)
            networkUsageUploadElement.innerHTML = formatBytes(data.networkStats.uploadMbps)
            networkUsagePingElement.innerHTML = data.networkStats.pingMs + 'ms'
            networkUsageInterfaceElement.innerHTML = data.networkStats.interface || 'N/A'
            networkUsagePingServerElement.innerHTML = data.networkStats.pingToIp
            networkLastCheckTimeElement.innerHTML = data.networkStats.lastCheckTime

            // update progress bars
            cpuProgress.style.width = data.diagnosticData.cpuUsage + '%'
            ramProgress.style.width = data.diagnosticData.ramUsage + '%'
            driveProgress.style.width = data.diagnosticData.driveSpace + '%'
            networkProgress.style.width = usagePercent + '%'

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
            networkProgress.style.background = usagePercent > 80
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

    setInterval(updateResourcesUsage, 5000)
    updateResourcesUsage()
})
