/** todo manager component functionality */
import Sortable from 'sortablejs'

document.addEventListener('DOMContentLoaded', function()
{
    let currentTodoId = null

    // get edit elements
    const editPopup = document.getElementById('editPopup')
    const editButtons = document.querySelectorAll('.fa-edit')
    const editTodoInput = document.getElementById('editTodoInput')
    const cancelEditButton = document.getElementById('cancelEditButton')
    const confirmEditButton = document.getElementById('confirmEditButton')

    // get info elements
    const infoPopup = document.getElementById('infoPopup')
    const todoOwner = document.getElementById('todoOwner')
    const todoStatus = document.getElementById('todoStatus')
    const todoClosedAt = document.getElementById('todoClosedAt')
    const todoItems = document.querySelectorAll('[data-todo-id]')
    const todoCreatedAt = document.getElementById('todoCreatedAt')
    const closePopupButton = document.getElementById('closePopup')

    // get todo delete elements
    let deleteUrl = ''
    const deleteButton = document.querySelectorAll('.delete-button')
    const deletePopupOverlay = document.getElementById('delete-popup-overlay')
    const deleteCancelButton = document.getElementById('delete-cancel-button')
    const deleteConfirmButton =  document.getElementById('delete-confirm-button')

    // handle edit button
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            if (!infoPopup.classList.contains('hidden')) {
                infoPopup.classList.add('hidden')
            }
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
            if (!editPopup.classList.contains('hidden')) {
                editPopup.classList.add('hidden')
                editTodoInput.value = ''
            }
            if (!infoPopup.classList.contains('hidden')) {
                infoPopup.classList.add('hidden')
            }
            if (!deletePopupOverlay.classList.contains('hidden')) {
                deletePopupOverlay.classList.add('hidden')
            }
        }
    })

    // edit confirm function
    function confirmEdit() {
        if (editTodoInput.value.length >= 1 && editTodoInput.value.length <= 2048) {
            window.location.href = `/manager/todo/edit?id=${currentTodoId}&todo=${encodeURIComponent(editTodoInput.value)}`
        } else {
            alert('Todo text must be between 1 and 2048 characters')
        }
    }

    // handle line up/down move keys
    editTodoInput.addEventListener("keydown", function (event) {
        if (event.altKey && (event.key === "ArrowUp" || event.key === "ArrowDown")) {
            event.preventDefault()

            let textarea = event.target
            let text = textarea.value
            let start = textarea.selectionStart
            let end = textarea.selectionEnd

            // split text into lines
            let lines = text.split("\n")
            let cursorLine = text.substring(0, start).split("\n").length - 1

            if ((event.key === "ArrowUp" && cursorLine > 0) || (event.key === "ArrowDown" && cursorLine < lines.length - 1)) {
                let swapLine = event.key === "ArrowUp" ? cursorLine - 1 : cursorLine + 1;
                [lines[cursorLine], lines[swapLine]] = [lines[swapLine], lines[cursorLine]] // swap lines

                // calculate new cursor position
                let beforeCursor = lines.slice(0, swapLine).join("\n").length + 1
                let cursorOffset = start - (text.substring(0, start).lastIndexOf("\n") + 1)
                let newCursorPos = beforeCursor + cursorOffset

                // update textarea
                textarea.value = lines.join("\n")
                textarea.setSelectionRange(newCursorPos, newCursorPos)
            }
        }
    })

    // for each todo item, attach a click event to its info button (if available)
    todoItems.forEach(item => {
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
        fetch(`/manager/todo/info?id=${todoId}`).then(response => response.json()).then(data => {
            showTodoInfoPopup(data)
        }).catch(
            error => console.error('Error fetching todo info:', error
        ))
    }

    // show todo info in popup
    function showTodoInfoPopup(todo) {
        if (todoOwner) todoOwner.textContent = `Owner: ${todo.owner}`
        if (todoStatus) todoStatus.textContent = `Status: ${todo.status}`
        if (todoCreatedAt) todoCreatedAt.textContent = `Created At: ${todo.created_at}`
        if (todoClosedAt) todoClosedAt.textContent = `Closed At: ${todo.closed_at}`
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
        return e.childNodes.length === 0 ? '' : e.childNodes[0].nodeValue
    }

    // close info popup when clicking outside of it
    document.getElementById('infoPopup').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // handle delete popup open
    deleteButton.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            deleteUrl = this.getAttribute('data-delete-url')
            deletePopupOverlay.classList.remove('hidden')
        })
    })

    // handle click on cancel in delete popup
    deleteCancelButton.addEventListener('click', function() {
        deletePopupOverlay.classList.add('hidden')
        deleteUrl = ''
    })

    // handle delete confirmation
    deleteConfirmButton.addEventListener('click', function() {
        if(deleteUrl) {
            window.location.href = deleteUrl
        }
    })

    // close info popup when clicking outside of it
    deletePopupOverlay.addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // initialize drag and drop functionality
    const todoItemsContainer = document.getElementById('todo-items-container')
    if (todoItemsContainer && document.querySelector('.todo-list')) {
        // check if we are on a mobile device
        const isMobile = window.innerWidth <= 768

        // initialize sortable with different options for mobile
        new Sortable(todoItemsContainer, {
            animation: 150,
            handle: isMobile ? null : '.drag-handle', // on mobile
            ghostClass: 'bg-neutral-600/80',
            delay: isMobile ? 300 : 0, // add delay for mobile to distinguish between tap and drag
            delayOnTouchOnly: true,    // only use delay on touch devices
            onEnd: function(evt) {
                // get all todo items
                const todoItems = Array.from(todoItemsContainer.querySelectorAll('.todo-item'))

                // create positions object
                const positions = {}
                todoItems.forEach((item, index) => {
                    const todoId = parseInt(item.dataset.todoId)
                    positions[todoId] = index + 1
                })

                // send positions to server
                updatePositions(positions)
            }
        })
    }

    // function to update positions on the server
    function updatePositions(positions) {
        const todoItems = document.querySelectorAll('.todo-item')
        todoItems.forEach(item => {
            item.style.transition = 'background-color 0.3s ease'
            item.style.backgroundColor = 'rgba(55, 65, 81, 0.9)'
        })
        fetch('/manager/todo/update-positions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(positions)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // show success feedback
                todoItems.forEach(item => {
                    item.style.backgroundColor = 'rgba(22, 101, 52, 0.5)'
                    setTimeout(() => {
                        item.style.backgroundColor = ''
                    }, 500)
                })
            } else {
                console.error('Error updating positions:', data.message)
                // show error feedback
                todoItems.forEach(item => {
                    item.style.backgroundColor = 'rgba(153, 27, 27, 0.5)'
                    setTimeout(() => {
                        item.style.backgroundColor = ''
                    }, 500)
                })
            }
        })
        .catch(error => {
            console.error('Error updating positions:', error)
            // show error feedback
            todoItems.forEach(item => {
                item.style.backgroundColor = 'rgba(153, 27, 27, 0.5)'
                setTimeout(() => {
                    item.style.backgroundColor = ''
                }, 500)
            })
        })
    }
})
