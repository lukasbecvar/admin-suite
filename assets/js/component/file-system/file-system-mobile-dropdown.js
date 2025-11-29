/** file-system item mobile dropdown functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // DROPDOWN CONTROL FUNCTIONS
    // -----------------------------
    // toggle dropdown menu
    function toggleDropdown(button) {
        const dropdown = button.closest('[data-dropdown]')
        const menu = dropdown.querySelector('[data-dropdown-menu]')

        // close all other dropdowns first
        closeAllDropdowns()

        // toggle current dropdown
        if (menu.classList.contains('hidden')) {
            // position dropdown smartly
            positionDropdown(button, menu)

            menu.classList.remove('hidden')
            button.classList.add('bg-gray-600/50')

            // add click outside listener
            setTimeout(() => {
                document.addEventListener('click', handleClickOutside)
            }, 10)
        } else {
            menu.classList.add('hidden')
            button.classList.remove('bg-gray-600/50')
            document.removeEventListener('click', handleClickOutside)
        }
    }

    // smart positioning for dropdown
    function positionDropdown(button, menu) {
        const buttonRect = button.getBoundingClientRect()
        const menuWidth = 192 // w-48 = 12rem = 192px
        const menuHeight = 200
        const viewportHeight = window.innerHeight
        const viewportWidth = window.innerWidth
        const spaceBelow = viewportHeight - buttonRect.bottom
        const spaceAbove = buttonRect.top

        // calculate position
        let top, left

        // vertical positioning
        if (spaceBelow < menuHeight && spaceAbove > menuHeight) {
            // show above
            top = buttonRect.top - menuHeight - 4
        } else {
            // show below
            top = buttonRect.bottom + 4
        }

        // horizontal positioning (align to right edge of button)
        left = buttonRect.right - menuWidth

        // ensure menu doesn't go off screen
        if (left < 8) {
            left = 8 // 8px margin from left edge
        }
        if (left + menuWidth > viewportWidth - 8) {
            left = viewportWidth - menuWidth - 8 // 8px margin from right edge
        }

        // apply positioning
        menu.style.position = 'fixed'
        menu.style.top = top + 'px'
        menu.style.left = left + 'px'
        menu.style.right = 'auto'
    }

    // close all dropdowns
    function closeAllDropdowns() {
        const allMenus = document.querySelectorAll('[data-dropdown-menu]')
        const allButtons = document.querySelectorAll('[data-dropdown] button')

        allMenus.forEach(menu => {
            menu.classList.add('hidden')
            // reset positioning
            menu.style.position = ''
            menu.style.top = ''
            menu.style.left = ''
            menu.style.right = ''
        })

        allButtons.forEach(button => {
            button.classList.remove('bg-gray-600/50')
        })

        document.removeEventListener('click', handleClickOutside)
    }

    // handle click outside dropdown
    function handleClickOutside(event) {
        const dropdown = event.target.closest('[data-dropdown]')
        if (!dropdown) {
            closeAllDropdowns()
        }
    }

    // close dropdown when clicking on menu item
    function handleMenuItemClick(event) {
        // dont close immediately for delete button (needs confirmation)
        if (!event.target.closest('.delete-file-button')) {
            closeAllDropdowns()
        }
    }

    // -----------------------------
    // INITIALIZATION AND GLOBAL EVENT LISTENERS
    // -----------------------------
    // add click handlers to menu items
    const menuItems = document.querySelectorAll('[data-dropdown-menu] a, [data-dropdown-menu] button')
    menuItems.forEach(item => {
        item.addEventListener('click', handleMenuItemClick)
    })
    
    // close dropdowns on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllDropdowns()
        }
    })
    
    // close dropdowns on scroll (mobile UX)
    window.addEventListener('scroll', function() {
        closeAllDropdowns()
    })
    
    // handle orientation change on mobile
    window.addEventListener('orientationchange', function() {
        setTimeout(closeAllDropdowns, 100)
    })

    // -----------------------------
    // GLOBAL EXPOSURE
    // -----------------------------
    // expose functions globally for template access
    window.toggleDropdown = toggleDropdown
    window.closeAllDropdowns = closeAllDropdowns
})
