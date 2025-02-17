/** system resources dashboard refresher functionality */
document.addEventListener('DOMContentLoaded', function() {
    // get progress bars
    const cpuProgress = document.getElementById('cpu-progress')
    const ramProgress = document.getElementById('ram-progress')
    const driveProgress = document.getElementById('drive-space-progress')

    // get info elements
    const cpuUsageElement = document.getElementById('cpu-usage')
    const ramUsageElement = document.getElementById('ram-usage')
    const systemStorageElement = document.getElementById('drive-space')
    const systemUptimeElement = document.getElementById('system-uptime')

    function updateResourcesUsage() {
        // get resources usage from API
        fetch('/api/system/resources').then(response => response.json()).then(data => {
            // update resources usage
            systemUptimeElement.innerHTML = data.hostUptime
            cpuUsageElement.innerHTML = 'CPU: ' + data.diagnosticData.cpuUsage + '%'
            ramUsageElement.innerHTML = 'RAM: (' + data.ramUsage.used + 'G / ' + data.diagnosticData.ramUsage + '%)'
            systemStorageElement.innerHTML = 'STORAGE: (' + data.storageUsage + 'G / ' + data.diagnosticData.driveSpace + '%)'

            // update progress bars
            cpuProgress.style.width = data.diagnosticData.cpuUsage + '%'
            ramProgress.style.width = data.diagnosticData.ramUsage + '%'
            driveProgress.style.width = data.diagnosticData.driveSpace + '%'

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
        }).catch(error => {
            console.log(error)
        })
    }

    setInterval(updateResourcesUsage, 8000)
    updateResourcesUsage()
})
