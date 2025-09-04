/** file-system media viewer functionality */
document.addEventListener('DOMContentLoaded', function () {
    // set up container height immediately
    fitImageToContainer()

    // initialize video player if present
    initializeVideoPlayer()

    // auto-hide loading spinner for images
    const images = document.querySelectorAll('img[onload]')
    images.forEach(img => {
        if (img.complete) {
            updateImageInfo(img)
        }
    })

    // add smooth transitions
    const mediaElements = document.querySelectorAll('#main-image, #main-video, #main-audio')
    mediaElements.forEach(element => {
        element.style.transition = 'transform 0.3s ease, opacity 0.3s ease'
    })

    // handle window resize
    window.addEventListener('resize', function () {
        fitImageToContainer()
    })

    // also fit container when image loads
    const mainImage = document.getElementById('main-image')
    if (mainImage) {
        mainImage.addEventListener('load', function () {
            fitImageToContainer()
        })
    }
})

// advanced image zoom state
let imageState = {
    scale: 1,
    translateX: 0,
    translateY: 0,
    isDragging: false,
    dragStartX: 0,
    dragStartY: 0,
    initialTranslateX: 0,
    initialTranslateY: 0,
    minScale: 0.5,
    maxScale: 10,
    zoomStep: 0.5
}

// update image details
function updateImageInfo(img) {
    const infoElement = document.getElementById('image-info')
    const loadingElement = document.getElementById('image-loading')
    const dimensionsElement = document.getElementById('file-dimensions')

    if (img.complete && img.naturalHeight !== 0) {
        const width = img.naturalWidth
        const height = img.naturalHeight
        infoElement.textContent = `${width} × ${height} pixels`

        // update dimensions in metadata
        if (dimensionsElement) {
            dimensionsElement.textContent = `${width} × ${height}`
        }

        // hide loading spinner
        if (loadingElement) {
            loadingElement.style.display = 'none'
        }
    }
}

// advanced zoom functions
function applyImageTransform() {
    const img = document.getElementById('main-image')
    if (img) {
        img.style.transform = `scale(${imageState.scale}) translate(${imageState.translateX}px, ${imageState.translateY}px)`
        img.style.cursor = imageState.scale > 1 ? 'grab' : 'zoom-in'

        if (imageState.scale > 1) {
            img.classList.add('z-10')
        } else {
            img.classList.remove('z-10')
        }
    }
}

// image zoom function
function zoomImage(delta, centerX = null, centerY = null) {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')
    if (!img || !container) return

    const oldScale = imageState.scale

    if (delta > 0) {
        imageState.scale = Math.min(imageState.scale + imageState.zoomStep, imageState.maxScale)
    } else {
        imageState.scale = Math.max(imageState.scale - imageState.zoomStep, imageState.minScale)
    }

    // if zoom point is specified, adjust translation to zoom towards that point
    if (centerX !== null && centerY !== null && oldScale !== imageState.scale) {
        const containerRect = container.getBoundingClientRect()
        const scaleChange = imageState.scale / oldScale

        // calculate relative position within container
        const relativeX = (centerX - containerRect.left) / containerRect.width - 0.5
        const relativeY = (centerY - containerRect.top) / containerRect.height - 0.5

        // adjust translation to zoom towards the point
        imageState.translateX = imageState.translateX * scaleChange - relativeX * containerRect.width * (scaleChange - 1) / imageState.scale
        imageState.translateY = imageState.translateY * scaleChange - relativeY * containerRect.height * (scaleChange - 1) / imageState.scale
    }

    constrainImagePosition()
    applyImageTransform()
}

// constrain image position
function constrainImagePosition() {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')
    if (!img || !container) return

    const containerRect = container.getBoundingClientRect()
    const imgRect = img.getBoundingClientRect()

    // calculate maximum allowed translation
    const maxTranslateX = Math.max(0, (imgRect.width * imageState.scale - containerRect.width) / 2 / imageState.scale)
    const maxTranslateY = Math.max(0, (imgRect.height * imageState.scale - containerRect.height) / 2 / imageState.scale)

    // constrain translation
    imageState.translateX = Math.max(-maxTranslateX, Math.min(maxTranslateX, imageState.translateX))
    imageState.translateY = Math.max(-maxTranslateY, Math.min(maxTranslateY, imageState.translateY))
}

// legacy function for backward compatibility
function toggleImageZoom() {
    if (imageState.scale <= 1) {
        zoomImage(1) // zoom in
    } else {
        resetImageZoom() // reset zoom
    }
}

// reset image zoom scale
function resetImageZoom() {
    imageState.scale = 1
    imageState.translateX = 0
    imageState.translateY = 0
    applyImageTransform()
}

// enable fullscreen mode
function toggleImageFullscreen() {
    const container = document.getElementById('image-container')
    if (container) {
        if (document.fullscreenElement) {
            document.exitFullscreen()
        } else {
            container.requestFullscreen().catch(err => {
                console.log('Error attempting to enable fullscreen:', err)
            })
        }
    }
}

// calculate optimal container height
function calculateContainerHeight() {
    // get viewport height
    const viewportHeight = window.innerHeight

    // calculate space taken by other elements
    const header = document.querySelector('.flex-shrink-0')
    const breadcrumb = document.querySelector('.px-2.py-3.border-b')
    const metadata = document.querySelector('.px-2.py-3.border-b:not(:first-child)')
    const controlBar = document.querySelector('.px-4.py-3.border-b')

    let usedHeight = 0
    if (header) usedHeight += header.offsetHeight
    if (breadcrumb) usedHeight += breadcrumb.offsetHeight
    if (metadata) usedHeight += metadata.offsetHeight
    if (controlBar) usedHeight += controlBar.offsetHeight

    // add padding and margin buffer
    usedHeight += 110

    return Math.max(400, viewportHeight - usedHeight)
}

// drag/pan functionality
let imageViewerInitialized = false

// initialize image drag functionality
function initializeImageDrag() {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')
    if (!img || !container) return

    // prevent multiple initialization
    if (imageViewerInitialized) return
    imageViewerInitialized = true

    // mouse events - only drag, no click zoom
    img.addEventListener('mousedown', startDrag)
    document.addEventListener('mousemove', drag)
    document.addEventListener('mouseup', endDrag)

    // touch events for mobile
    img.addEventListener('touchstart', startDragTouch, { passive: false })
    document.addEventListener('touchmove', dragTouch, { passive: false })
    document.addEventListener('touchend', endDrag)

    // wheel zoom - this is the main zoom method
    container.addEventListener('wheel', handleWheel, { passive: false })

    // prevent context menu on right click
    img.addEventListener('contextmenu', e => e.preventDefault())
}

// drag/pan start
function startDrag(e) {
    imageState.isDragging = false // start as false, will become true on first move
    imageState.dragStartX = e.clientX
    imageState.dragStartY = e.clientY
    imageState.initialTranslateX = imageState.translateX
    imageState.initialTranslateY = imageState.translateY

    e.preventDefault()
}

// touch drag/pan start
function startDragTouch(e) {
    if (e.touches.length !== 1) return

    const touch = e.touches[0]
    imageState.isDragging = false
    imageState.dragStartX = touch.clientX
    imageState.dragStartY = touch.clientY
    imageState.initialTranslateX = imageState.translateX
    imageState.initialTranslateY = imageState.translateY

    e.preventDefault()
}

// drag/pan
function drag(e) {
    if (imageState.dragStartX === undefined) return

    const deltaX = e.clientX - imageState.dragStartX
    const deltaY = e.clientY - imageState.dragStartY
    const dragThreshold = 3 // minimum pixels to start dragging

    // only start dragging if mouse moved enough
    if (!imageState.isDragging && (Math.abs(deltaX) > dragThreshold || Math.abs(deltaY) > dragThreshold)) {
        imageState.isDragging = true
        const img = document.getElementById('main-image')
        if (img) {
            img.style.cursor = 'grabbing'
            img.classList.add('dragging')
        }
    }

    if (imageState.isDragging) {
        imageState.translateX = imageState.initialTranslateX + deltaX
        imageState.translateY = imageState.initialTranslateY + deltaY
        constrainImagePosition()
        applyImageTransform()
    }

    e.preventDefault()
}

// touch drag/pan
function dragTouch(e) {
    if (e.touches.length !== 1 || imageState.dragStartX === undefined) return

    const touch = e.touches[0]
    const deltaX = touch.clientX - imageState.dragStartX
    const deltaY = touch.clientY - imageState.dragStartY
    const dragThreshold = 3

    if (!imageState.isDragging && (Math.abs(deltaX) > dragThreshold || Math.abs(deltaY) > dragThreshold)) {
        imageState.isDragging = true
    }

    if (imageState.isDragging) {
        imageState.translateX = imageState.initialTranslateX + deltaX
        imageState.translateY = imageState.initialTranslateY + deltaY

        constrainImagePosition()
        applyImageTransform()
    }

    e.preventDefault()
}

// drag/pan end
function endDrag() {
    // only process if we were actually in a drag state
    if (imageState.dragStartX === undefined) return

    imageState.isDragging = false
    imageState.dragStartX = undefined
    imageState.dragStartY = undefined

    const img = document.getElementById('main-image')
    if (img) {
        img.style.cursor = imageState.scale > 1 ? 'grab' : 'zoom-in'
        img.classList.remove('dragging')
    }
}

// handle wheel zoom
function handleWheel(e) {
    e.preventDefault()
    e.stopPropagation()

    const delta = e.deltaY > 0 ? -1 : 1
    zoomImage(delta, e.clientX, e.clientY)
}

// fit image to container
function fitImageToContainer() {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')

    if (img && container) {
        // set container height dynamically
        const optimalHeight = calculateContainerHeight()
        container.style.height = optimalHeight + 'px'

        // reset zoom state
        resetImageZoom()

        // initialize drag functionality
        initializeImageDrag()
    }
}

// update video details
function updateVideoInfo(video) {
    const infoElement = document.getElementById('video-info')
    if (video.duration && !isNaN(video.duration)) {
        const duration = formatDuration(video.duration)
        const width = video.videoWidth
        const height = video.videoHeight
        infoElement.textContent = `${width} × ${height} • ${duration}`
    }
}

// toggle video player in fullscreen mode
function toggleVideoFullscreen() {
    const video = document.getElementById('main-video')
    if (video) {
        if (document.fullscreenElement) {
            document.exitFullscreen()
        } else {
            video.requestFullscreen().catch(err => {
                console.log('Error attempting to enable fullscreen:', err)
            })
        }
    }
}

// video error handling
function handleVideoError(video) {
    console.error('Video error:', video.error)
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/20 border border-red-500/30 rounded">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
                <span class="text-red-300">Error loading video</span>
            </div>
        `
        statusElement.classList.remove('hidden')
    }
}

// handle buffering
function handleVideoWaiting() {
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-800/50 rounded">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-400"></div>
                <span class="text-gray-300">Buffering...</span>
            </div>
        `
        statusElement.classList.remove('hidden')
    }
}

// handle video can play
function handleVideoCanPlay() {
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.classList.add('hidden')
    }
}

// handle stalled video (network issues or something wrong with resource streaming)
function handleVideoStalled(video) {
    console.warn('Video stalled, attempting to recover...')
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-500/20 border border-yellow-500/30 rounded">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-yellow-400"></div>
                <span class="text-yellow-300">Connection issues, retrying...</span>
            </div>
        `
        statusElement.classList.remove('hidden')
    }

    // try to recover from stalled state
    setTimeout(() => {
        if (video.readyState < 3) {
            video.load() // reload the video
        }
    }, 3000)
}

// advanced video management
function initializeVideoPlayer() {
    const video = document.getElementById('main-video')
    if (!video) return

    // handle network state changes
    video.addEventListener('loadstart', function () {
        video.networkState = video.NETWORK_LOADING
    })

    // prevent memory leaks
    video.addEventListener('ended', function () {
        // optional: pause and reset to beginning
        video.currentTime = 0
    })

    // handle seeking issues
    video.addEventListener('seeking', function () {
        const statusElement = document.getElementById('video-status')
        if (statusElement) {
            statusElement.innerHTML = `
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-500/20 border border-blue-500/30 rounded">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-400"></div>
                    <span class="text-blue-300">Seeking...</span>
                </div>
            `
            statusElement.classList.remove('hidden')
        }
    })

    video.addEventListener('seeked', function () {
        const statusElement = document.getElementById('video-status')
        if (statusElement) {
            statusElement.classList.add('hidden')
        }
    })
}

// audio viewer functionality
function updateAudioInfo(audio) {
    const durationElement = document.getElementById('audio-duration')
    if (audio.duration && !isNaN(audio.duration)) {
        const duration = formatDuration(audio.duration)
        durationElement.textContent = `Duration: ${duration}`
    }
}

// format audio duration to readable format
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = Math.floor(seconds % 60)

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    } else {
        return `${minutes}:${secs.toString().padStart(2, '0')}`
    }
}

// keyboard shortcuts
document.addEventListener('keydown', function (e) {
    // ESC key - exit fullscreen or reset zoom
    if (e.key === 'Escape') {
        if (document.fullscreenElement) {
            document.exitFullscreen()
        } else if (imageState.scale > 1) {
            resetImageZoom()
        }
    }

    // plus/minus keys for zoom
    if (e.key === '+' || e.key === '=') {
        const img = document.getElementById('main-image')
        if (img) {
            e.preventDefault()
            zoomImage(1)
        }
    }

    if (e.key === '-' || e.key === '_') {
        const img = document.getElementById('main-image')
        if (img) {
            e.preventDefault()
            zoomImage(-1)
        }
    }

    // arrow keys for panning when zoomed
    if (imageState.scale > 1) {
        const panStep = 50
        let panX = 0, panY = 0

        if (e.key === 'ArrowLeft') {
            panX = panStep
            e.preventDefault()
        } else if (e.key === 'ArrowRight') {
            panX = -panStep
            e.preventDefault()
        } else if (e.key === 'ArrowUp') {
            panY = panStep
            e.preventDefault()
        } else if (e.key === 'ArrowDown') {
            panY = -panStep
            e.preventDefault()
        }

        if (panX !== 0 || panY !== 0) {
            imageState.translateX += panX
            imageState.translateY += panY
            constrainImagePosition()
            applyImageTransform()
        }
    }

    // space key - play/pause video or audio
    if (e.key === ' ' || e.code === 'Space') {
        const video = document.getElementById('main-video')
        const audio = document.getElementById('main-audio')

        if (video && document.activeElement !== video) {
            e.preventDefault()
            if (video.paused) {
                video.play()
            } else {
                video.pause()
            }
        } else if (audio && document.activeElement !== audio) {
            e.preventDefault()
            if (audio.paused) {
                audio.play()
            } else {
                audio.pause()
            }
        }
    }

    // f key - toggle fullscreen
    if (e.key === 'f' || e.key === 'F') {
        const video = document.getElementById('main-video')
        const imageContainer = document.getElementById('image-container')

        if (video) {
            e.preventDefault()
            toggleVideoFullscreen()
        } else if (imageContainer) {
            e.preventDefault()
            toggleImageFullscreen()
        }
    }
})

// expose functions globally for access in template
window.resetImageZoom = resetImageZoom
window.updateImageInfo = updateImageInfo
window.toggleImageZoom = toggleImageZoom
window.updateVideoInfo = updateVideoInfo
window.updateAudioInfo = updateAudioInfo
window.handleVideoError = handleVideoError
window.handleVideoWaiting = handleVideoWaiting
window.handleVideoCanPlay = handleVideoCanPlay
window.handleVideoStalled = handleVideoStalled
window.toggleImageFullscreen = toggleImageFullscreen
window.toggleVideoFullscreen = toggleVideoFullscreen
