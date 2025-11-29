/** Loading Component Functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // INITIAL PAGE LOAD HIDING
    // -----------------------------
    // hide loading component after page load
    document.getElementById('loader-wrapper').style.display = 'none'

    // -----------------------------
    // LINK CLICK LOADING
    // -----------------------------
    document.body.addEventListener('click', function (event) {
        const target = event.target.closest('a')
        const loader = document.getElementById('loader-wrapper')
        if (target && target.href) {
            // exclude links with id loading-blocker
            if (target.id === 'loading-blocker') {
                return
            }

            // allow links that explicitly open in a new context
            if (target.target && target.target !== '_self') {
                return
            }

            // exclude links to internal blobs and data views
            if (!target.href.includes('http') || target.href.includes('blob')) {
                return
            }

            event.preventDefault()
            loader.style.display = 'flex'
            setTimeout(() => {
                window.location.href = target.href
            }, 10)
        }
    })

    // -----------------------------
    // HISTORY NAVIGATION HIDING
    // -----------------------------
    // fix disable loading when user navigates step back in history
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            document.getElementById('loader-wrapper').style.display = 'none'
        }
    })
})
