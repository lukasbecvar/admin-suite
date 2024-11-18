/* todo manager component (handle edit popup input function) */
document.addEventListener('DOMContentLoaded', function () {
    let currentTodoId = null

    // get page elements
    const editPopup = document.getElementById('editPopup')
    const editButtons = document.querySelectorAll('.fa-edit')
    const editTodoInput = document.getElementById('editTodoInput')
    const cancelEditButton = document.getElementById('cancelEditButton')
    const confirmEditButton = document.getElementById('confirmEditButton')

    // get raw string from escaped data
    function decodeInput(input) {
        const e = document.createElement('div')
        e.innerHTML = input
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue
    }

    // handle edit button
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
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
            editPopup.classList.add('hidden')
            editTodoInput.value = ''
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

    // prevent multiple clicks on delete link
    document.querySelectorAll('.delete-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            // prevent multiple clicks
            if (link.getAttribute('data-clicked') === 'true') {
                event.preventDefault()
                return
            }

            // mark link as clicked
            link.setAttribute('data-clicked', 'true')
        })
    })
})
