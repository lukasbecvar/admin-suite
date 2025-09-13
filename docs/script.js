// admin-suite documentation page functionality script
document.addEventListener('DOMContentLoaded', () => {
    // --- responsive menu --- //
    const menuToggle = document.getElementById('menu-toggle')
    const navList = document.getElementById('nav-list')

    menuToggle.addEventListener('click', () => {
        navList.classList.toggle('active')
        menuToggle.classList.toggle('active')
    })

    // --- smooth scrolling --- //
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault()
            navList.classList.remove('active')
            menuToggle.classList.remove('active')
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            })
        })
    })

    // --- scroll animations --- //
    const faders = document.querySelectorAll('.fade-in')
    const appearOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    }

    const appearOnScroll = new IntersectionObserver(function (entries, appearOnScroll) {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                return
            }
            entry.target.classList.add('visible')
            appearOnScroll.unobserve(entry.target)
        })
    }, appearOptions)

    faders.forEach(fader => {
        appearOnScroll.observe(fader)
    })

    // --- gallery lightbox --- //
    const documentationData = [
        {
            image: 'assets/admin-suite-preview/1-dashboard.png',
            title: 'Dashboard',
            description: 'The main dashboard provides a comprehensive overview of the system. It displays real-time information about running processes, network usage, system resources, and database statistics.'
        },
        {
            image: 'assets/admin-suite-preview/2-monitoring.png',
            title: 'Monitoring',
            description: 'The monitoring section allows you to track the status of internal and external services. It provides a log of recent events and shows the SLA history for each service.'
        },
        {
            image: 'assets/admin-suite-preview/3-monitoring-service-details.png',
            title: 'Service Details',
            description: 'This view provides detailed metrics for a specific service, including visitor counts and total visitors over time. It also shows the HTTP service details like URL, max response time, and accepted codes.'
        },
        {
            image: 'assets/admin-suite-preview/4-metrics-dashboard.png',
            title: 'Metrics Dashboard',
            description: 'The metrics dashboard displays historical data for CPU, RAM, and disk usage. This helps in analyzing the system\'s performance over a period of time.'
        },
        {
            image: 'assets/admin-suite-preview/5-database-manager.png',
            title: 'Database Manager',
            description: 'The database manager provides an overview of all databases, including their size and the number of tables. It. also shows important database statistics like uptime, connected threads, and queries.'
        },
        {
            image: 'assets/admin-suite-preview/6-logs-manager.png',
            title: 'Logs Manager',
            description: 'The logs manager displays a list of system logs. You can see details like the message, time, browser, OS, IP address, and the user associated with each log entry.'
        },
        {
            image: 'assets/admin-suite-preview/7-file-system-manager.png',
            title: 'File System Browser',
            description: 'The file system browser allows you to navigate the server\'s file system. It shows file and folder names, sizes, permissions, and modification dates.'
        },
        {
            image: 'assets/admin-suite-preview/8-terminal-component.png',
            title: 'Terminal',
            description: 'An interactive terminal to execute commands directly on the server. It also includes a server panel for quick actions like starting or stopping services.'
        },
        {
            image: 'assets/admin-suite-preview/9-system-auditor.png',
            title: 'System Auditor',
            description: 'The system auditor displays security and audit information, including system security rules, SSH access history, and journal live logs.'
        },
        {
            image: 'assets/admin-suite-preview/10-diagnostics.png',
            title: 'System Diagnostics',
            description: 'The diagnostics page runs a series of checks to ensure the system and the suite are running correctly. It checks for things like required packages, log file sizes, and storage usage.'
        },
        {
            image: 'assets/admin-suite-preview/11-users-manager.png',
            title: 'Users Manager',
            description: 'The users manager lists all users with their roles, browser, OS, last login, and IP address. You can manage users and their permissions from this page.'
        },
        {
            image: 'assets/admin-suite-preview/12-user-details-view.png',
            title: 'User Profile',
            description: 'The user profile page shows detailed information about a specific user, including their logs, IP geolocation information, and ban status.'
        },
        {
            image: 'assets/admin-suite-preview/13-config-manager.png',
            title: 'Settings',
            description: 'The settings page allows you to manage account settings, suite configuration, and feature flags.'
        },
        {
            image: 'assets/admin-suite-preview/14-suite-configuration.png',
            title: 'Suite Configuration',
            description: 'This page allows you to manage suite-wide configuration files. You can view the status of each configuration file.'
        },
        {
            image: 'assets/admin-suite-preview/15-feature-flags-config.png',
            title: 'Feature Flags',
            description: 'The feature flags page allows you to enable or disable features of the application in real-time.'
        }
    ]

    const galleryContainer = document.getElementById('preview-container')
    const lightbox = document.getElementById('lightbox')
    const lightboxImg = document.getElementById('lightbox-img')
    const lightboxCaption = document.getElementById('lightbox-caption')
    const closeBtn = document.querySelector('.lightbox-close')
    const prevBtn = document.querySelector('.lightbox-prev')
    const nextBtn = document.querySelector('.lightbox-next')

    let currentIndex = 0

    documentationData.forEach((item, index) => {
        const galleryItem = document.createElement('div')
        galleryItem.classList.add('preview-item')
        galleryItem.dataset.index = index

        const image = document.createElement('img')
        image.src = item.image
        image.alt = item.title

        const caption = document.createElement('div')
        caption.classList.add('caption')

        const title = document.createElement('h3')
        title.textContent = item.title

        const description = document.createElement('p')
        description.textContent = item.description

        caption.appendChild(title)
        caption.appendChild(description)

        galleryItem.appendChild(image)
        galleryItem.appendChild(caption)

        galleryItem.addEventListener('click', () => {
            openLightbox(index)
        })

        galleryContainer.appendChild(galleryItem)
    })

    function openLightbox(index) {
        currentIndex = index
        updateLightboxContent()
        lightbox.style.display = 'block'
        document.body.classList.add('lightbox-open')
    }

    function closeLightbox() {
        lightbox.style.display = 'none'
        document.body.classList.remove('lightbox-open')
    }

    function showNext() {
        currentIndex = (currentIndex + 1) % documentationData.length
        updateLightboxContent()
    }

    function showPrev() {
        currentIndex = (currentIndex - 1 + documentationData.length) % documentationData.length
        updateLightboxContent()
    }

    function updateLightboxContent() {
        const item = documentationData[currentIndex]
        lightboxImg.src = item.image
        lightboxCaption.innerHTML = `<h3>${item.title}</h3><p>${item.description}</p>`
    }

    closeBtn.addEventListener('click', closeLightbox)
    prevBtn.addEventListener('click', showPrev)
    nextBtn.addEventListener('click', showNext)
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox()
        }
    })

    // get scroll to top button
    let mybutton = document.getElementById("scrollToTopBtn")
    window.onscroll = function () { scrollFunction() }

    function scrollFunction() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            mybutton.style.display = "flex"
        } else {
            mybutton.style.display = "none"
        }
    }

    mybutton.addEventListener("click", topFunction)

    function topFunction() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        })
    }
})

window.onload = function() {
    const preloader = document.getElementById('preloader')
    preloader.style.opacity = '0'
    setTimeout(() => {
        preloader.style.display = 'none'
    }, 500)
}
