document.addEventListener('DOMContentLoaded', function () {
    let currentTodoId = null;

    // get page elements
    const editButtons = document.querySelectorAll('.fa-edit');
    const editPopup = document.getElementById('editPopup');
    const editTodoInput = document.getElementById('editTodoInput');
    const cancelEditButton = document.getElementById('cancelEditButton');
    const confirmEditButton = document.getElementById('confirmEditButton');

    // handle edit button
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            currentTodoId = this.closest('button').dataset.todoId;
            editPopup.classList.remove('hidden');
            editTodoInput.focus();
        });
    });

    // handle cancel button
    cancelEditButton.addEventListener('click', function () {
        editPopup.classList.add('hidden');
        editTodoInput.value = '';
    });

    // handle confirm button
    confirmEditButton.addEventListener('click', function () {
        if (editTodoInput.value.length >= 1 && editTodoInput.value.length <= 255) {
            window.location.href = `/manager/todo/edit?id=${currentTodoId}&todo=${encodeURIComponent(editTodoInput.value)}`;
        } else {
            alert('Todo text must be between 1 and 255 characters');
        }
    });

    // handle escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            editPopup.classList.add('hidden');
            editTodoInput.value = '';
        }
    });
});
