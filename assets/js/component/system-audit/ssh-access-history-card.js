/** ssh access history card lazy loading */
document.addEventListener('DOMContentLoaded', function()
{
	// -----------------------------
	// ELEMENT DECLARATIONS
	// -----------------------------
	const container = document.getElementById('ssh-access-history-container')
	const loading = document.getElementById('ssh-access-history-loading')
	const emptyState = document.getElementById('ssh-access-history-empty')
	const errorState = document.getElementById('ssh-access-history-error')
	const list = document.getElementById('ssh-access-history-list')

	// if card is not on the page, skip
	if (!container || !loading || !emptyState || !errorState || !list) {
		return
	}

	// -----------------------------
	// RENDER HELPERS
	// -----------------------------
	function showLoading() {
		loading.classList.remove('hidden')
		emptyState.classList.add('hidden')
		errorState.classList.add('hidden')
		list.classList.add('hidden')
	}

	function showEmpty() {
		loading.classList.add('hidden')
		emptyState.classList.remove('hidden')
		errorState.classList.add('hidden')
		list.classList.add('hidden')
	}

	function showError() {
		loading.classList.add('hidden')
		emptyState.classList.add('hidden')
		errorState.classList.remove('hidden')
		list.classList.add('hidden')
	}

	function showList() {
		loading.classList.add('hidden')
		emptyState.classList.add('hidden')
		errorState.classList.add('hidden')
		list.classList.remove('hidden')
	}

	function createLoginRow(login) {
		const row = document.createElement('div')
		row.className = 'flex items-center justify-between p-3 bg-gray-700/30 hover:bg-gray-700/50 rounded transition-colors duration-200'

		const left = document.createElement('div')
		left.className = 'flex flex-col'

		const userEl = document.createElement('div')
		userEl.className = 'text-sm font-medium text-white'
		userEl.textContent = login.user || 'Unknown user'

		const dateEl = document.createElement('div')
		dateEl.className = 'text-xs text-green-400'
		dateEl.textContent = login.date || ''

		left.appendChild(userEl)
		left.appendChild(dateEl)

		const right = document.createElement('div')
		right.className = 'text-right text-sm text-gray-300'

		const hostEl = document.createElement('div')
		hostEl.className = 'font-medium'
		hostEl.textContent = login.host || ''

		const ipEl = document.createElement('div')
		ipEl.className = 'text-xs text-gray-400'
		const ip = login.ip || ''
		const port = login.port || ''
		ipEl.textContent = ip && port ? `${ip}:${port}` : ip || ''

		right.appendChild(hostEl)
		right.appendChild(ipEl)

		row.appendChild(left)
		row.appendChild(right)

		return row
	}

	// -----------------------------
	// DATA FETCHING
	// -----------------------------
	async function fetchSshAccessHistory() {
		showLoading()

		try {
			const res = await fetch('/api/system/ssh-access-history')
			if (!res.ok) {
				throw new Error('Network response was not ok')
			}

			const data = await res.json()
			const history = Array.isArray(data.ssh_access_history) ? data.ssh_access_history : []

			// no data
			if (history.length === 0) {
				showEmpty()
				list.innerHTML = ''
				return
			}

			// render list
			list.innerHTML = ''
			history.forEach(login => {
				list.appendChild(createLoginRow(login))
			})

			showList()
		} catch (e) {
			console.error('Failed to load ssh access history:', e)
			showError()
		}
	}

	// -----------------------------
	// INITIALIZATION
	// -----------------------------
	fetchSshAccessHistory()
})
