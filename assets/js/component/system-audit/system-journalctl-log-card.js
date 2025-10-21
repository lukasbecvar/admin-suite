/* system journalctl log card functionality */
document.addEventListener('DOMContentLoaded', function()
{	
	// get dom elements
	const ul = document.getElementById('journalctl-logs')
	const scrollBox = document.getElementById('journalctl-scrollbox')
	const waitingForLogs = document.getElementById('waiting-for-logs')

	// format timestamp to human readable
	function formatTimestamp(isoString) {
		try {
			const date = new Date(isoString)
			const day = date.getDate().toString().padStart(2, '0')
			const month = (date.getMonth() + 1).toString().padStart(2, '0')
			const year = date.getFullYear()
			const hours = date.getHours().toString().padStart(2, '0')
			const minutes = date.getMinutes().toString().padStart(2, '0')
			const seconds = date.getSeconds().toString().padStart(2, '0')

			return `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`
		} catch {
			return isoString
		}
	}

	// fetch logs from server
	async function fetchLogs() {
		// check if scroll is near bottom (tolerance 10px)
		const isAtBottom = scrollBox.scrollHeight - scrollBox.scrollTop <= scrollBox.clientHeight + 10

		try {
			const res = await fetch('/api/system/logs')
			if (!res.ok) throw new Error('Network response was not ok')

			const data = await res.json()
			const logs = data.logs

			// remove waiting message
			if (logs[0] != '') {
				waitingForLogs.style = 'display: none'
			}

			logs.forEach(log => {
				const li = document.createElement('li')
				li.classList.add('whitespace-pre-wrap', 'font-mono', 'text-sm')

				// ISO log format parsing
				const match = log.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\+\d{4})?) (\S+) (\S+):\s?(.*)$/)
				if (match) {
					const [, timestamp, host, unit, message] = match

					const timeSpan = document.createElement('span')
					timeSpan.textContent = `${formatTimestamp(timestamp)} `
					timeSpan.className = 'text-green-400'

					const unitSpan = document.createElement('span')
					unitSpan.textContent = `${unit}: `
					unitSpan.className = 'text-purple-400'

					const msgSpan = document.createElement('span')
					msgSpan.textContent = message || '(no message)'
					msgSpan.className = 'text-white'

					li.appendChild(timeSpan)
					li.appendChild(unitSpan)
					li.appendChild(msgSpan)
				} else {
					// fallback to raw log
					li.textContent = log
				}

				ul.appendChild(li)
			})

			// scroll if needed
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
