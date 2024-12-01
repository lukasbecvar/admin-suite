/* loading component functionality */ 
document.addEventListener("DOMContentLoaded", function() {
    // hide loading component after page load
    document.getElementById("loader-wrapper").style.display = "none"
})

/* loading component for click on links */
document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById("loader-wrapper")
    loader.style.display = "none"
    document.body.addEventListener("click", function (event) {
        const target = event.target.closest("a")
        if (target && target.href) {
            event.preventDefault()
            loader.style.display = "flex"
            setTimeout(() => {
                window.location.href = target.href
            }, 10)
        }
    })
})
