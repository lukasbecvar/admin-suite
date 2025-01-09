document.addEventListener('DOMContentLoaded', function () {
    let currentTodoId = null
    let pressTimer
    
    // get edit elements
    const editPopup = document.getElementById('editPopup')
    const editButtons = document.querySelectorAll('.fa-edit')
    const editTodoInput = document.getElementById('editTodoInput')
    const cancelEditButton = document.getElementById('cancelEditButton')
    const confirmEditButton = document.getElementById('confirmEditButton')

    // get info elements
    const todoItems = document.querySelectorAll('[data-todo-id]')
    const infoPopup = document.getElementById('infoPopup')
    const todoOwner = document.getElementById('todoOwner')
    const todoStatus = document.getElementById('todoStatus')
    const todoCreatedAt = document.getElementById('todoCreatedAt')
    const todoClosedAt = document.getElementById('todoClosedAt')
    const closePopupButton = document.getElementById('closePopup')

    // handle edit button
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            // close info popup if is open
            if (!infoPopup.classList.contains('hidden')) {
                infoPopup.classList.add('hidden')
            }

            // clear any ongoing press timer to prevent info popup
            clearTimeout(pressTimer)

            currentTodoId = this.closest('button').dataset.todoId
            const todoText = this.closest('button').dataset.todoText
            editPopup.classList.remove('hidden')
            editTodoInput.value = decodeInput(todoText)
            editTodoInput.focus()
        })
    })

    // handle cancel button
    cancelEditButton.addEventListener('click', function () {
        editPopup.classList.add('hidden')
        editTodoInput.value = ''
    })

    // handle confirm button
    confirmEditButton.addEventListener('click', function () {
        confirmEdit()
    })

    // handle escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            // close edit popup
            if (!editPopup.classList.contains('hidden')) {
                editPopup.classList.add('hidden')
                editTodoInput.value = ''
            }

            // close info popup
            if (!infoPopup.classList.contains('hidden')) {
                infoPopup.classList.add('hidden')
            }
        }
    })

    // edit confirm function
    function confirmEdit() {
        if (editTodoInput.value.length >= 1 && editTodoInput.value.length <= 512) {
            window.location.href = `/manager/todo/edit?id=${currentTodoId}&todo=${encodeURIComponent(editTodoInput.value)}`
        } else {
            alert('Todo text must be between 1 and 512 characters')
        }
    }

    // handle long press on todo item for mobile (on mobile, use long press)
    todoItems.forEach(item => {
        item.addEventListener('touchstart', function () {
            // long press detection for mobile
            pressTimer = setTimeout(() => {
                if (editPopup.classList.contains('hidden')) {
                    currentTodoId = item.dataset.todoId;
                    getTodoInfo(currentTodoId);
                }
            }, 800) // 800ms long press threshold
        })

        item.addEventListener('touchend', function () {
            clearTimeout(pressTimer) // cancel long press if touch ends early
        })

        item.addEventListener('touchmove', function () {
            clearTimeout(pressTimer) // cancel long press if touch moves
        })

        // info button click for desktop (only on desktop)
        const infoButton = item.querySelector('.info-button')
        if (infoButton) {
            infoButton.addEventListener('click', function () {
                currentTodoId = item.dataset.todoId
                getTodoInfo(currentTodoId)
            })
        }
    })

    // fetch todo info by id
    function getTodoInfo(todoId) {
        fetch(`/manager/todo/info?id=${todoId}`)
            .then(response => response.json())
            .then(data => {
                showTodoInfoPopup(data)
            })
            .catch(error => console.error('Error fetching todo info:', error))
    }

    // show todo info in popup
    function showTodoInfoPopup(todo) {
        // set todo info content to popup
        if (todoOwner) todoOwner.textContent = `Owner: ${todo.owner}`
        if (todoStatus) todoStatus.textContent = `Status: ${todo.status}`
        if (todoCreatedAt) todoCreatedAt.textContent = `Created At: ${todo.created_at}`
        if (todoClosedAt) todoClosedAt.textContent = `Closed At: ${todo.closed_at}`

        // show info popup
        infoPopup.classList.remove('hidden')
    }

    // close info popup when close button is clicked
    closePopupButton.addEventListener('click', function () {
        infoPopup.classList.add('hidden')
    })

    // decode input from escaped HTML
    function decodeInput(input) {
        const e = document.createElement('div')
        e.innerHTML = input
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue
    }
})
