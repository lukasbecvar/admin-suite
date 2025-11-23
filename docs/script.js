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

    // --- preview fullscreen viewer --- //
    const previewCards = document.querySelectorAll('.preview-card')
    const sliderState = new Map()
    const lightbox = document.getElementById('lightbox')
    const lightboxImg = document.getElementById('lightbox-img')
    const lightboxCaption = document.getElementById('lightbox-caption')
    const closeBtn = document.querySelector('.lightbox-close')
    const prevBtn = document.querySelector('.lightbox-prev')
    const nextBtn = document.querySelector('.lightbox-next')

    setupPreviewSliders()
    const previewData = collectPreviewData()

    let currentCardIndex = 0
    let currentSlideIndex = 0

    function openLightbox(cardIndex, slideIndex = 0) {
        if (!lightbox) return
        currentCardIndex = cardIndex
        currentSlideIndex = slideIndex
        updateLightbox()
        lightbox.classList.add('visible')
        document.body.classList.add('lightbox-open')
        lightbox.setAttribute('aria-hidden', 'false')
    }

    function closeLightbox() {
        if (!lightbox) return
        lightbox.classList.remove('visible')
        document.body.classList.remove('lightbox-open')
        lightbox.setAttribute('aria-hidden', 'true')
    }

    function showNextPreview() {
        if (!previewData.length) return
        const slides = previewData[currentCardIndex]?.slides || []
        if (slides.length && currentSlideIndex < slides.length - 1) {
            currentSlideIndex += 1
        } else {
            currentCardIndex = (currentCardIndex + 1) % previewData.length
            currentSlideIndex = 0
        }
        updateLightbox()
    }

    function showPrevPreview() {
        if (!previewData.length) return
        const slides = previewData[currentCardIndex]?.slides || []
        if (currentSlideIndex > 0) {
            currentSlideIndex -= 1
        } else {
            currentCardIndex = (currentCardIndex - 1 + previewData.length) % previewData.length
            const previousSlides = previewData[currentCardIndex]?.slides || []
            currentSlideIndex = Math.max(previousSlides.length - 1, 0)
        }
        updateLightbox()
    }

    function updateLightbox() {
        const data = previewData[currentCardIndex]
        if (!data || !lightboxImg || !lightboxCaption) return
        const slide = data.slides[currentSlideIndex] || data.slides[0] || {}
        lightboxImg.src = slide.src || ''
        lightboxImg.alt = slide.alt || data.title
        const slideLabel = slide.label ? `<p class=\"lightbox-slide-label\">${slide.label}</p>` : ''
        lightboxCaption.innerHTML = `<h3>${data.title}</h3>${slideLabel}${data.description}`
    }

    previewCards.forEach((card, index) => {
        const trigger = card.querySelector('.preview-media')
        if (trigger) {
            trigger.addEventListener('click', () => openLightbox(index, getActiveSlideIndex(index)))
            trigger.addEventListener('keypress', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault()
                    openLightbox(index, getActiveSlideIndex(index))
                }
            })
        }
    })

    closeBtn?.addEventListener('click', closeLightbox)
    prevBtn?.addEventListener('click', showPrevPreview)
    nextBtn?.addEventListener('click', showNextPreview)

    lightbox?.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            closeLightbox()
        }
    })

    document.addEventListener('keydown', (event) => {
        if (!lightbox?.classList.contains('visible')) {
            return
        }
        if (event.key === 'Escape') {
            closeLightbox()
        } else if (event.key === 'ArrowRight') {
            showNextPreview()
        } else if (event.key === 'ArrowLeft') {
            showPrevPreview()
        }
    })

    function setupPreviewSliders() {
        previewCards.forEach((card, cardIndex) => {
            const media = card.querySelector('.preview-media')
            const slider = media?.querySelector('.preview-slider')
            const slides = slider ? Array.from(slider.querySelectorAll('.preview-slide')) : []
            if (!slider || !slides.length) {
                return
            }

            sliderState.set(cardIndex, 0)
            slider.dataset.activeSlide = '0'
            slides.forEach((slide, index) => {
                const isActive = index === 0
                slide.classList.toggle('active', isActive)
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true')
            })

            if (slides.length <= 1) {
                return
            }

            const prevBtn = document.createElement('button')
            prevBtn.type = 'button'
            prevBtn.className = 'preview-control preview-control-prev'
            prevBtn.setAttribute('aria-label', 'Show previous screenshot')
            prevBtn.innerHTML = '&#10094;'

            const nextBtn = document.createElement('button')
            nextBtn.type = 'button'
            nextBtn.className = 'preview-control preview-control-next'
            nextBtn.setAttribute('aria-label', 'Show next screenshot')
            nextBtn.innerHTML = '&#10095;'

            const dots = document.createElement('div')
            dots.className = 'preview-dots'

            const goToSlide = (targetIndex) => {
                const total = slides.length
                const normalized = (targetIndex + total) % total
                slides.forEach((slide, slideIndex) => {
                    const isActive = slideIndex === normalized
                    slide.classList.toggle('active', isActive)
                    slide.setAttribute('aria-hidden', isActive ? 'false' : 'true')
                })
                slider.dataset.activeSlide = String(normalized)
                sliderState.set(cardIndex, normalized)
                dots.querySelectorAll('.preview-dot').forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === normalized)
                })
            }

            slides.forEach((slide, slideIndex) => {
                const dot = document.createElement('button')
                dot.type = 'button'
                dot.className = 'preview-dot'
                const label = slide.dataset.label || `Screenshot ${slideIndex + 1}`
                dot.setAttribute('aria-label', `Show ${label}`)
                dot.addEventListener('click', (event) => {
                    event.stopPropagation()
                    event.preventDefault()
                    goToSlide(slideIndex)
                })
                dots.appendChild(dot)
            })

            prevBtn.addEventListener('click', (event) => {
                event.stopPropagation()
                event.preventDefault()
                const current = Number(slider.dataset.activeSlide) || 0
                goToSlide(current - 1)
            })

            nextBtn.addEventListener('click', (event) => {
                event.stopPropagation()
                event.preventDefault()
                const current = Number(slider.dataset.activeSlide) || 0
                goToSlide(current + 1)
            })

            media?.appendChild(prevBtn)
            media?.appendChild(nextBtn)
            media?.appendChild(dots)
            goToSlide(0)
        })
    }

    function collectPreviewData() {
        return Array.from(previewCards).map(card => {
            const title = card.querySelector('h3')?.textContent?.trim() || ''
            const body = card.querySelector('.preview-body')
            const slides = Array.from(card.querySelectorAll('.preview-slide')).map(slide => ({
                src: slide.getAttribute('src') || '',
                alt: slide.getAttribute('alt') || title,
                label: slide.dataset.label || ''
            }))
            return {
                title,
                description: body ? body.innerHTML : '',
                slides: slides.length ? slides : [{
                    src: '',
                    alt: title,
                    label: ''
                }]
            }
        })
    }

    function getActiveSlideIndex(cardIndex) {
        return sliderState.get(cardIndex) ?? 0
    }

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
