/* loading component functionality */ 
document.addEventListener("DOMContentLoaded", function() {
    // hide loading component after page load
    document.getElementById("loader-wrapper").style.display = "none"
})

/* loading component for click on links */
document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        const target = event.target.closest("a")
        const loader = document.getElementById("loader-wrapper")
        if (target && target.href) {

            // exclude links to internal blobs and data views
            if (!target.href.includes("http") || target.href.includes("blob")) {
                return
            }

            event.preventDefault()
            loader.style.display = "flex"
            setTimeout(() => {
                window.location.href = target.href
            }, 10)
        }
    })
})
