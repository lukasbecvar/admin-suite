/** todo manager component functionality */
import Sortable from 'sortablejs'

document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // EDIT POPUP
    // -----------------------------
    let currentEditId = null
    const editPopup = document.getElementById('editPopup')
    const editTodoInput = document.getElementById('editTodoInput')
    const cancelEditButton = document.getElementById('cancelEditButton')
    const confirmEditButton = document.getElementById('confirmEditButton')

    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function () {
            currentEditId = this.dataset.editId
            editTodoInput.value = decodeInput(this.dataset.todoText)
            editPopup.classList.remove('hidden')
            editTodoInput.focus()
        })
    })

    cancelEditButton.addEventListener('click', function () {
        editPopup.classList.add('hidden')
        editTodoInput.value = ''
    })

    confirmEditButton.addEventListener('click', function () {
        const csrfToken = document.getElementById('todo-csrf-token').dataset.csrf

        if (editTodoInput.value.length >= 1 && editTodoInput.value.length <= 2048) {
            const form = new URLSearchParams()
            form.append('csrf_token', csrfToken)
            form.append('id', currentEditId)
            form.append('todo', editTodoInput.value)

            fetch('/manager/todo/edit', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            }).then(() => window.location.reload())
        } else {
            alert('Todo text must be between 1 and 2048 characters')
        }
    })

    // -----------------------------
    // DELETE POPUP
    // -----------------------------
    let deleteFormId = null
    const deletePopupOverlay = document.getElementById('delete-popup-overlay')
    const deleteCancelButton = document.getElementById('delete-cancel-button')
    const deleteConfirmButton = document.getElementById('delete-confirm-button')

    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', function () {
            deleteFormId = this.dataset.formId
            deletePopupOverlay.classList.remove('hidden')
        })
    })

    deleteCancelButton.addEventListener('click', function () {
        deletePopupOverlay.classList.add('hidden')
        deleteFormId = null
    })

    deleteConfirmButton.addEventListener('click', function () {
        if (deleteFormId) {
            document.getElementById(deleteFormId).submit()
        }
    })

    // -----------------------------
    // CLOSE / REOPEN (direct POST submit)
    // -----------------------------
    document.querySelectorAll('.todo-close-button, .todo-reopen-button').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById(this.dataset.formId).submit()
        })
    })

    // -----------------------------
    // INFO POPUP
    // -----------------------------
    const infoPopup = document.getElementById('infoPopup')
    const todoOwner = document.getElementById('todoOwner')
    const todoStatus = document.getElementById('todoStatus')
    const todoClosedAt = document.getElementById('todoClosedAt')
    const todoCreatedAt = document.getElementById('todoCreatedAt')
    const closePopupButton = document.getElementById('closePopup')

    document.querySelectorAll('.info-button').forEach(button => {
        button.addEventListener('click', function () {
            const todoId = this.dataset.todoId
            fetch(`/manager/todo/info?id=${todoId}`)
                .then(r => r.json())
                .then(data => showTodoInfoPopup(data))
        })
    })

    function showTodoInfoPopup(todo) {
        todoOwner.textContent = `Owner: ${todo.owner}`
        todoStatus.textContent = `Status: ${todo.status}`
        todoCreatedAt.textContent = `Created At: ${todo.created_at}`
        todoClosedAt.textContent = `Closed At: ${todo.closed_at}`
        infoPopup.classList.remove('hidden')
    }

    closePopupButton.addEventListener('click', function () {
        infoPopup.classList.add('hidden')
    })

    // -----------------------------
    // UPDATE POSITIONS (POST + CSRF)
    // -----------------------------
    const todoItemsContainer = document.getElementById('todo-items-container')

    if (todoItemsContainer) {
        const isMobile = window.innerWidth <= 768

        new Sortable(todoItemsContainer, {
            animation: 150,
            handle: isMobile ? null : '.drag-handle',
            ghostClass: 'bg-neutral-600/80',
            delay: isMobile ? 300 : 0,
            delayOnTouchOnly: true,
            onEnd: function () {
                const items = Array.from(todoItemsContainer.querySelectorAll('.todo-item'))
                const positions = {}

                items.forEach((item, index) => {
                    positions[item.dataset.todoId] = index + 1
                })

                updatePositions(positions)
            }
        })
    }

    function updatePositions(positions) {
        const csrfToken = document.getElementById('todo-csrf-token').dataset.csrf

        const form = new URLSearchParams()
        form.append('csrf_token', csrfToken)

        // build positions as PHP-style array: positions[123] = 1
        Object.keys(positions).forEach(todoId => {
            form.append(`positions[${todoId}]`, positions[todoId])
        })

        const items = document.querySelectorAll('.todo-item')
        items.forEach(i => i.style.transition = 'background-color 0.3s ease')
        items.forEach(i => i.style.backgroundColor = 'rgba(55,65,81,0.9)')

        fetch('/manager/todo/update-positions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: form,
            credentials: 'same-origin'
        }).then(r => r.json()).then(data => {
            const ok = data.success
            const color = ok ? 'rgba(22,101,52,0.5)' : 'rgba(153,27,27,0.5)'

            items.forEach(i => {
                i.style.backgroundColor = color
                setTimeout(() => {
                    i.style.backgroundColor = ''
                }, 500)
            })
        }).catch(() => {
            items.forEach(i => {
                i.style.backgroundColor = 'rgba(153,27,27,0.5)'
                setTimeout(() => {
                    i.style.backgroundColor = ''
                }, 500)
            })
        })
    }
    
    // -----------------------------
    // UTILITIES
    // -----------------------------
    function decodeInput(input) {
        const e = document.createElement('div')
        e.innerHTML = input
        return e.childNodes.length === 0 ? '' : e.childNodes[0].nodeValue
    }
})

// -----------------------------
// PREVENT MULTIPLE SUBMISSIONS
// -----------------------------
document.addEventListener('submit', (event) =>
{
    const submitButton = event.target.querySelector('.add-todo-button')
    if (submitButton) {
        submitButton.disabled = true
        submitButton.classList.add('opacity-50', 'cursor-not-allowed')
    }
})
