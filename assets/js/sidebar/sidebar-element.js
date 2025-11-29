/* sidebar element functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // get elements
    const sidebar = document.getElementById('sidebar')
    const mainContent = document.getElementById('main-content')
    
    // swipe properties
    let touchStartX = 0
    let touchEndX = 0
    let touchStartY = 0
    let touchEndY = 0
    let isHorizontalSwipe = true

    // check if any element on the page has horizontal overflow at the start
    function hasHorizontalOverflowAtStart() {
        const elements = document.querySelectorAll('*')
        for (const element of elements) {
            if (element.scrollWidth > element.clientWidth && element.scrollLeft === 0) {
                return true
            }
        }
        return false
    }

    // check if any element on the page has horizontal overflow
    function hasHorizontalOverflow() {
        const elements = document.querySelectorAll('*')
        for (const element of elements) {
            if (element.scrollWidth > element.clientWidth) {
                return true
            }
        }
        return false
    }

    // setup swipe detection for an element
    function setupSwipeDetection(element) {
        let touchedElement = null
        element.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX
            touchStartY = e.changedTouches[0].screenY
            touchEndX = touchStartX
            touchEndY = touchStartY
            isHorizontalSwipe = true
                touchedElement = document.elementFromPoint(
                e.changedTouches[0].clientX,
                e.changedTouches[0].clientY
            )
        })
    
        element.addEventListener('touchmove', function(e) {
            touchEndX = e.changedTouches[0].screenX
            touchEndY = e.changedTouches[0].screenY
            const diffX = Math.abs(touchEndX - touchStartX)
            const diffY = Math.abs(touchEndY - touchStartY)
            if (diffY > diffX) {
                isHorizontalSwipe = false
            }
        })
    
        element.addEventListener('touchend', function() {
            const swipeThreshold = 80
            const swipeDistance = touchEndX - touchStartX
                if (isHorizontalSwipe && Math.abs(swipeDistance) > swipeThreshold) {
                let el = touchedElement
                while (el) {
                    const canScroll = el.scrollWidth > el.clientWidth && ((swipeDistance > 0 && el.scrollLeft > 0) || (swipeDistance < 0 && el.scrollLeft < el.scrollWidth - el.clientWidth))
                    if (canScroll) {
                        return
                    }
                    el = el.parentElement
                }
                if (swipeDistance > swipeThreshold) {
                    sidebar.classList.add('active')
                    mainContent.classList.add('active')
                } else if (swipeDistance < -swipeThreshold) {
                    sidebar.classList.remove('active')
                    mainContent.classList.remove('active')
                }
            }
        })
    }

    // setup swipe detection for main content
    setupSwipeDetection(mainContent)

    // setup swipe detection for sidebar
    setupSwipeDetection(sidebar)

    // toggle button
    document.getElementById('menu-toggle').addEventListener('click', function() {
        sidebar.classList.toggle('active')
        mainContent.classList.toggle('active')
    })
})
