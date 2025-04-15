/* system journalctl log card functionality */
document.addEventListener('DOMContentLoaded', () => {
	const ul = document.getElementById('journalctl-logs')
	const scrollBox = document.getElementById('journalctl-scrollbox')
	const waitingForLogs = document.getElementById('waiting-for-logs')

	async function fetchLogs() {
		// check if scroll is near bottom (tolerance 10px)
		const isAtBottom = scrollBox.scrollHeight - scrollBox.scrollTop <= scrollBox.clientHeight + 10

		try {
			const res = await fetch('/api/system/logs')
			if (!res.ok) throw new Error('Network response was not ok')

			const data = await res.json()
			const logs = data.logs

			// remove waiting message
			if (logs.length > 0 && waitingForLogs) {
				waitingForLogs.remove()
			}

			logs.forEach(log => {
				const li = document.createElement('li')
				li.classList.add('whitespace-pre-wrap', 'font-mono', 'text-sm')

				// ISO log format parsing
				const match = log.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\+\d{4})?) (\S+) (\S+):\s?(.*)$/)
				if (match) {
					const [, timestamp, host, unit, message] = match

					const timeSpan = document.createElement('span')
					timeSpan.textContent = `${timestamp} `
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
