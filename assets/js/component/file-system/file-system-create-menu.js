/** file-system create menu functionality */
document.addEventListener('DOMContentLoaded', function()
{
    const createMenu = document.getElementById('create-menu')
    const createMenuButton = document.getElementById('create-menu-button')

    // toggle menu when button is clicked
    createMenuButton.addEventListener('click', function(e) {
        e.stopPropagation()
        createMenu.classList.toggle('hidden')
    })

    // hide menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!createMenuButton.contains(e.target) && !createMenu.contains(e.target)) {
            createMenu.classList.add('hidden')
        }
    })

    // close menu with escape key press
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !createMenu.classList.contains('hidden')) {
            createMenu.classList.add('hidden')
        }
    })
})
