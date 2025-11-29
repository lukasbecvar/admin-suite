/** sidebar toggle functionality */
document.addEventListener('DOMContentLoaded', function () {
    // -----------------------------
    // SIDEBAR INITIALIZATION ON DOMContentLoaded
    // -----------------------------
    const sidebar = document.getElementById('sidebar')
    const mainContent = document.getElementById('main-content')

    if (window.innerWidth > 400) {
        // small delay to ensure smooth animation
        setTimeout(() => {
            sidebar.classList.add('active')
            mainContent.classList.add('active')
        }, 50)
    }
})
