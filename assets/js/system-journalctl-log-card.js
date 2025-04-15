/* system journalctl log card functionality */
document.addEventListener('DOMContentLoaded', function() {
    async function fetchLogs() {
        const scrollBox = document.getElementById('journalctl-scrollbox')
        const ul = document.getElementById('journalctl-logs')
        const waitingForLogs = document.getElementById('waiting-for-logs')
    
        // check if scroll is near bottom (tolerance 10px)
        const isAtBottom = scrollBox.scrollHeight - scrollBox.scrollTop <= scrollBox.clientHeight + 10
    
        try {
            const res = await fetch('/api/system/logs')
            if (!res.ok) throw new Error('Network response was not ok')
    
            const data = await res.json()
            const logs = data.logs
    
            // disable waiting for logs message
            if (logs[0] != '') {
                waitingForLogs.style.display = 'none'
            }

            logs.forEach(log => {
                const li = document.createElement('li')
                li.textContent = log
                ul.appendChild(li)
            })
    
            // scroll to bottom
            if (isAtBottom) {
                scrollBox.scrollTop = scrollBox.scrollHeight
            }
        } catch (err) {
            console.error('Failed to fetch logs:', err)
        }
    }      

    // fetch initially and then every 10 seconds
    fetchLogs()
    setInterval(fetchLogs, 10000)
})
