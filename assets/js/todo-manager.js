/** todo manager component functionality */
document.addEventListener('DOMContentLoaded', function () {
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
})
