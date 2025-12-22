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

    // event listener for edit button
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function () {
            currentEditId = this.dataset.editId
            editTodoInput.value = decodeInput(this.dataset.todoText)
            editPopup.classList.remove('hidden')
            editTodoInput.focus()
        })
    })

    // event listener for cancel button
    cancelEditButton.addEventListener('click', function () {
        editPopup.classList.add('hidden')
        editTodoInput.value = ''
    })

    // event listener for confirmation submit
    let isEditSubmitting = false
    confirmEditButton.addEventListener('click', function () {
        // prevent multiple submissions
        if (isEditSubmitting) {
            return
        }

        const csrfToken = document.getElementById('todo-csrf-token').dataset.csrf

        if (editTodoInput.value.length >= 1 && editTodoInput.value.length <= 2048) {
            isEditSubmitting = true
            this.disabled = true
            this.classList.add('opacity-50', 'cursor-not-allowed')
            this.textContent = 'Saving...'

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

    // alt + arrow keys for line reordering in edit textarea
    editTodoInput.addEventListener('keydown', function (event) {
        if (event.altKey && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
            event.preventDefault()
                
            const lines = this.value.split('\n')
            const cursorPos = this.selectionStart
            const cursorEnd = this.selectionEnd
                
            // find current line index and cursor position within line
            let currentLineIndex = 0
            let charPosInLine = 0
            let tempPos = 0
                
            for (let i = 0; i < lines.length; i++) {
                if (tempPos + lines[i].length >= cursorPos) {
                    currentLineIndex = i
                    charPosInLine = cursorPos - tempPos
                    break
                }
                tempPos += lines[i].length + 1 // +1 for \n
            }
                
            let newCursorPos = cursorPos
            let moved = false
                
            if (event.key === 'ArrowUp' && currentLineIndex > 0) {
                // move line up
                [lines[currentLineIndex - 1], lines[currentLineIndex]] = [lines[currentLineIndex], lines[currentLineIndex - 1]]
                moved = true
                    
                // calculate new cursor position
                if (currentLineIndex === 1) {
                    newCursorPos = charPosInLine
                } else {
                    let newTempPos = 0
                    for (let i = 0; i < currentLineIndex - 1; i++) {
                        newTempPos += lines[i].length + 1
                    }
                    newCursorPos = newTempPos + charPosInLine
                }
            } else if (event.key === 'ArrowDown' && currentLineIndex < lines.length - 1) {
                // move line down  
                [lines[currentLineIndex], lines[currentLineIndex + 1]] = [lines[currentLineIndex + 1], lines[currentLineIndex]]
                moved = true
                    
                // calculate new cursor position
                let newTempPos = 0
                for (let i = 0; i <= currentLineIndex; i++) {
                    newTempPos += lines[i].length + 1
                }
                newCursorPos = newTempPos + charPosInLine
            }
                
            if (moved) {
                this.value = lines.join('\n')
                    
                // set cursor to the moved line, preserving position within line
                const selectionLength = cursorEnd - cursorPos
                this.setSelectionRange(newCursorPos, newCursorPos + selectionLength)
                    
                // auto-resize after content change
                this.style.height = 'auto'
                this.style.height = (this.scrollHeight) + 'px'
            }
        }
    })

    // -----------------------------
    // DELETE POPUP
    // -----------------------------
    let deleteFormId = null
    const deletePopupOverlay = document.getElementById('delete-popup-overlay')
    const deleteCancelButton = document.getElementById('delete-cancel-button')
    const deleteConfirmButton = document.getElementById('delete-confirm-button')

    // event listener for delete button
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', function () {
            deleteFormId = this.dataset.formId
            deletePopupOverlay.classList.remove('hidden')
        })
    })

    // event listener for cancel button
    deleteCancelButton.addEventListener('click', function () {
        deletePopupOverlay.classList.add('hidden')
        deleteFormId = null
    })

    // event listener for confirmation submit
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

    // event listener for info button
    document.querySelectorAll('.info-button').forEach(button => {
        button.addEventListener('click', function () {
            const todoId = this.dataset.todoId
            fetch(`/manager/todo/info?id=${todoId}`)
                .then(r => r.json())
                .then(data => showTodoInfoPopup(data))
        })
    })

    // show todo info popup
    function showTodoInfoPopup(todo) {
        todoOwner.textContent = `Owner: ${todo.owner}`
        todoStatus.textContent = `Status: ${todo.status}`
        todoCreatedAt.textContent = `Created At: ${todo.created_at}`
        todoClosedAt.textContent = `Closed At: ${todo.closed_at}`
        infoPopup.classList.remove('hidden')
    }

    // event listener for close button
    closePopupButton.addEventListener('click', function () {
        infoPopup.classList.add('hidden')
    })

    // -----------------------------
    // UPDATE POSITIONS (POST + CSRF)
    // -----------------------------
    const todoScrollContainer = document.getElementById('todo-csrf-token')
    const todoItemsContainer = document.getElementById('todo-items-container')
    if (todoItemsContainer) {
        const isMobile = window.innerWidth <= 768
        new Sortable(todoItemsContainer, {
            animation: 150,
            handle: isMobile ? null : '.drag-handle',
            ghostClass: 'bg-neutral-600/80',
            delay: isMobile ? 300 : 0,
            delayOnTouchOnly: true,
            onMove: function (evt) {
                handleDragAutoScroll(evt.originalEvent, todoScrollContainer)
            },
            onEnd: function () {
                stopDragAutoScroll()

                const items = Array.from(todoItemsContainer.querySelectorAll('.todo-item'))
                const positions = {}

                items.forEach((item, index) => {
                    positions[item.dataset.todoId] = index + 1
                })
                updatePositions(positions)
            }
        })
    }

    // update positions
    function updatePositions(positions) {
        if (!todoScrollContainer) {
            return
        }
        const csrfToken = todoScrollContainer.dataset.csrf

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
    let scrollAnimationFrame = null
    let scrollDirection = null

    function handleDragAutoScroll(originalEvent, scrollContainer) {
        if (!scrollContainer) {
            stopDragAutoScroll()
            return
        }

        const clientY = getClientY(originalEvent)
        if (clientY === null) {
            return
        }

        const rect = scrollContainer.getBoundingClientRect()
        const topThreshold = rect.top + 80
        const bottomThreshold = rect.bottom - 80
        const maxScrollTop = scrollContainer.scrollHeight - scrollContainer.clientHeight

        if (clientY < topThreshold && scrollContainer.scrollTop > 0) {
            startDragAutoScroll('up', scrollContainer)
        } else if (clientY > bottomThreshold && scrollContainer.scrollTop < maxScrollTop) {
            startDragAutoScroll('down', scrollContainer)
        } else {
            stopDragAutoScroll()
        }
    }

    function getClientY(evt) {
        if (!evt) {
            return null
        }

        if (evt.touches && evt.touches.length > 0) {
            return evt.touches[0].clientY
        }

        if (evt.clientY !== undefined) {
            return evt.clientY
        }

        if (evt.pageY !== undefined) {
            return evt.pageY
        }

        return null
    }

    function startDragAutoScroll(direction, scrollContainer) {
        if (scrollDirection === direction) {
            return
        }

        scrollDirection = direction

        const step = () => {
            if (scrollDirection === 'up') {
                scrollContainer.scrollTop = Math.max(0, scrollContainer.scrollTop - 12)
            } else if (scrollDirection === 'down') {
                scrollContainer.scrollTop = Math.min(
                    scrollContainer.scrollHeight - scrollContainer.clientHeight,
                    scrollContainer.scrollTop + 12
                )
            } else {
                return
            }

            scrollAnimationFrame = requestAnimationFrame(step)
        }

        if (scrollAnimationFrame) {
            cancelAnimationFrame(scrollAnimationFrame)
        }

        scrollAnimationFrame = requestAnimationFrame(step)
    }

    function stopDragAutoScroll() {
        scrollDirection = null

        if (scrollAnimationFrame) {
            cancelAnimationFrame(scrollAnimationFrame)
            scrollAnimationFrame = null
        }
    }

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

document.addEventListener('DOMContentLoaded', function() {
    // -----------------------------
    // NEW TODO TEXTAREA
    // -----------------------------
    const newTodoTextarea = document.getElementById('create_todo_form_todo_text')

    if (newTodoTextarea) {
        let isSubmitting = false

        // auto-resize textarea
        newTodoTextarea.addEventListener('input', function () {
            this.style.height = 'auto'
            this.style.height = (this.scrollHeight) + 'px'
        })

        // submit on enter, new line on shift+enter
        newTodoTextarea.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault()
                
                // prevent multiple submissions
                if (isSubmitting) {
                    return
                }
                
                isSubmitting = true
                const form = this.closest('form')
                const submitButton = form.querySelector('.add-todo-button')
                
                if (submitButton) {
                    submitButton.disabled = true
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed')
                }
                
                form.submit()
            }
        })

        // reset submitting flag on input change
        newTodoTextarea.addEventListener('input', function () {
            isSubmitting = false
        })

        // alt + arrow keys for line reordering
        newTodoTextarea.addEventListener('keydown', function (event) {
            if (event.altKey && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
                event.preventDefault()
                
                const lines = this.value.split('\n')
                const cursorPos = this.selectionStart
                const cursorEnd = this.selectionEnd
                
                // find current line index and cursor position within line
                let currentLineIndex = 0
                let charPosInLine = 0
                let tempPos = 0
                
                for (let i = 0; i < lines.length; i++) {
                    if (tempPos + lines[i].length >= cursorPos) {
                        currentLineIndex = i
                        charPosInLine = cursorPos - tempPos
                        break
                    }
                    tempPos += lines[i].length + 1 // +1 for \n
                }
                
                let newCursorPos = cursorPos
                let moved = false
                
                if (event.key === 'ArrowUp' && currentLineIndex > 0) {
                    // move line up
                    [lines[currentLineIndex - 1], lines[currentLineIndex]] = [lines[currentLineIndex], lines[currentLineIndex - 1]]
                    moved = true
                    
                    // calculate new cursor position
                    if (currentLineIndex === 1) {
                        newCursorPos = charPosInLine
                    } else {
                        let newTempPos = 0
                        for (let i = 0; i < currentLineIndex - 1; i++) {
                            newTempPos += lines[i].length + 1
                        }
                        newCursorPos = newTempPos + charPosInLine
                    }
                } else if (event.key === 'ArrowDown' && currentLineIndex < lines.length - 1) {
                    // move line down  
                    [lines[currentLineIndex], lines[currentLineIndex + 1]] = [lines[currentLineIndex + 1], lines[currentLineIndex]]
                    moved = true
                    
                    // calculate new cursor position
                    let newTempPos = 0
                    for (let i = 0; i <= currentLineIndex; i++) {
                        newTempPos += lines[i].length + 1
                    }
                    newCursorPos = newTempPos + charPosInLine
                }
                
                if (moved) {
                    this.value = lines.join('\n')
                    
                    // set cursor to the moved line, preserving position within line
                    const selectionLength = cursorEnd - cursorPos
                    this.setSelectionRange(newCursorPos, newCursorPos + selectionLength)
                    
                    // auto-resize after content change
                    this.style.height = 'auto'
                    this.style.height = (this.scrollHeight) + 'px'
                }
            }
        })
    }
})
