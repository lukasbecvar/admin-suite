/** system resources dashboard refresher functionality */
document.addEventListener('DOMContentLoaded', function() {
    // get progress bars
    const cpuProgress = document.getElementById('cpu-progress')
    const ramProgress = document.getElementById('ram-progress')
    const driveProgress = document.getElementById('drive-space-progress')
    const networkProgress = document.getElementById('network-progress')

    // get info elements
    const cpuUsageElement = document.getElementById('cpu-usage')
    const ramUsageElement = document.getElementById('ram-usage')
    const systemStorageElement = document.getElementById('drive-space')
    const systemUptimeElement = document.getElementById('system-uptime')
    const networkUsageElement = document.getElementById('network-usage')
    const networkUsageDownloadElement = document.getElementById('network-usage-download')
    const networkUsageUploadElement = document.getElementById('network-usage-upload')
    const networkUsagePingElement = document.getElementById('network-usage-ping')
    const networkUsageInterfaceElement = document.getElementById('network-usage-interface')
    const networkUsagePingServerElement = document.getElementById('network-usage-ping-server')
    const networkLastCheckTimeElement = document.getElementById('network-last-check-time')

    function updateResourcesUsage() {
        // get resources usage from API
        fetch('/api/system/resources').then(response => response.json()).then(data => {
            // update resources usage
            systemUptimeElement.innerHTML = data.hostUptime
            cpuUsageElement.innerHTML = 'CPU: ' + data.diagnosticData.cpuUsage + '%'
            ramUsageElement.innerHTML = 'RAM: (' + data.ramUsage.used + 'G / ' + data.diagnosticData.ramUsage + '%)'
            systemStorageElement.innerHTML = 'STORAGE: (' + data.storageUsage + 'G / ' + data.diagnosticData.driveSpace + '%)'

            // update network usage
            networkUsageElement.innerHTML = 'Usage: ' + data.networkStats.networkUsagePercent + '%'
            networkUsageDownloadElement.innerHTML = data.networkStats.downloadMbps + 'Mbps'
            networkUsageUploadElement.innerHTML = data.networkStats.uploadMbps + 'Mbps'
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
                ? 'linear-gradient(45deg, #f73925, #ff6b6b)' 
                : 'linear-gradient(45deg,rgb(0, 182, 233), #0072ff)'
                
            ramProgress.style.background = data.diagnosticData.ramUsage > 80 
                ? 'linear-gradient(45deg, #f73925, #ff6b6b)' 
                : 'linear-gradient(45deg,rgb(0, 182, 233), #0072ff)'
                
            driveProgress.style.background = data.diagnosticData.driveSpace > 80 
                ? 'linear-gradient(45deg, #f73925, #ff6b6b)' 
                : 'linear-gradient(45deg,rgb(0, 182, 233), #0072ff)'

            networkProgress.style.background = data.networkStats.networkUsagePercent > 80 
                ? 'linear-gradient(45deg, #f73925, #ff6b6b)' 
                : 'linear-gradient(45deg,rgb(0, 182, 233), #0072ff)'
        }).catch(error => {
            console.log(error)
        })
    }

    setInterval(updateResourcesUsage, 10000)
    updateResourcesUsage()
})
