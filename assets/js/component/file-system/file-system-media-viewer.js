/** file-system media viewer functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // GLOBAL VARIABLES AND INITIAL SETUP
    // -----------------------------
    // set up container height immediately
    fitImageToContainer()

    // initialize video player if present
    initializeVideoPlayer()
    initializeVideoControls()
    initializeAudioControls()

    // auto-hide loading spinner for images
    const images = document.querySelectorAll('img[onload]')
    images.forEach(img => {
        if (img.complete) {
            updateImageInfo(img)
        }
    })

    // add smooth transitions
    const mediaElements = document.querySelectorAll('#main-video, #main-audio')
    mediaElements.forEach(element => {
        element.style.transition = 'transform 0.3s ease, opacity 0.3s ease'
    })

    const imageElement = document.getElementById('main-image')
    if (imageElement) {
        setImageTransition(true)
    }

    // handle window resize
    window.addEventListener('resize', function () {
        fitImageToContainer()
    })

    // also fit container when image loads
    const mainImage = document.getElementById('main-image')
    if (mainImage) {
        mainImage.addEventListener('load', function () {
            fitImageToContainer()
            updateBaseImageDimensions()
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
    zoomStep: 0.3,
    baseWidth: 0,
    baseHeight: 0
}

// -----------------------------
// IMAGE VIEWER FUNCTIONALITY
// -----------------------------
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

        updateBaseImageDimensions()
    }
}

// advanced zoom functions
function applyImageTransform() {
    const img = document.getElementById('main-image')
    if (img) {
        img.style.transformOrigin = 'center center'
        img.style.transform = `translate(${imageState.translateX}px, ${imageState.translateY}px) scale(${imageState.scale})`
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
    const direction = delta > 0 ? 1 : -1
    const nextScale = imageState.scale + direction * imageState.zoomStep
    const newScale = clamp(nextScale, imageState.minScale, imageState.maxScale)

    if (newScale === oldScale) {
        constrainImagePosition()
        applyImageTransform()
        return
    }

    imageState.scale = newScale
    const scaleChange = newScale / oldScale

    imageState.translateX *= scaleChange
    imageState.translateY *= scaleChange

    constrainImagePosition()
    applyImageTransform()
}

// constrain image position
function constrainImagePosition() {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')
    if (!img || !container) return

    const containerWidth = container.clientWidth
    const containerHeight = container.clientHeight
    const baseWidth = imageState.baseWidth || img.clientWidth
    const baseHeight = imageState.baseHeight || img.clientHeight
    const scaledWidth = baseWidth * imageState.scale
    const scaledHeight = baseHeight * imageState.scale

    const maxTranslateX = Math.max(0, (scaledWidth - containerWidth) / 2)
    const maxTranslateY = Math.max(0, (scaledHeight - containerHeight) / 2)

    // constrain translation
    imageState.translateX = clamp(imageState.translateX, -maxTranslateX, maxTranslateX)
    imageState.translateY = clamp(imageState.translateY, -maxTranslateY, maxTranslateY)
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
    if (!canPan()) return

    imageState.isDragging = false // start as false, will become true on first move
    imageState.dragStartX = e.clientX
    imageState.dragStartY = e.clientY
    imageState.initialTranslateX = imageState.translateX
    imageState.initialTranslateY = imageState.translateY

    setImageTransition(false)
    e.preventDefault()
}

// touch drag/pan start
function startDragTouch(e) {
    if (e.touches.length !== 1) return
    if (!canPan()) return

    const touch = e.touches[0]
    imageState.isDragging = false
    imageState.dragStartX = touch.clientX
    imageState.dragStartY = touch.clientY
    imageState.initialTranslateX = imageState.translateX
    imageState.initialTranslateY = imageState.translateY

    setImageTransition(false)
    e.preventDefault()
}

// drag/pan
function drag(e) {
    if (imageState.dragStartX === undefined || !canPan()) return

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
    if (e.touches.length !== 1 || imageState.dragStartX === undefined || !canPan()) return

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
        setImageTransition(true)
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
        updateBaseImageDimensions()

        // initialize drag functionality
        initializeImageDrag()
    }
}

function updateBaseImageDimensions() {
    const img = document.getElementById('main-image')
    if (!img) return

    const width = img.clientWidth
    const height = img.clientHeight

    if (width > 0 && height > 0) {
        imageState.baseWidth = width
        imageState.baseHeight = height
    }
}

function canPan() {
    const img = document.getElementById('main-image')
    const container = document.getElementById('image-container')
    if (!img || !container) return false

    const baseWidth = imageState.baseWidth || img.clientWidth
    const baseHeight = imageState.baseHeight || img.clientHeight
    const scaledWidth = baseWidth * imageState.scale
    const scaledHeight = baseHeight * imageState.scale

    return scaledWidth > container.clientWidth + 2 || scaledHeight > container.clientHeight + 2
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max)
}

function setImageTransition(enabled) {
    const img = document.getElementById('main-image')
    if (!img) return

    img.style.transition = enabled ? 'transform 0.12s ease-out, opacity 0.3s ease' : 'none'
}

// -----------------------------
// VIDEO PLAYER FUNCTIONALITY
// -----------------------------
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
        statusElement.textContent = 'Buffering...'
        statusElement.style.opacity = '1'
    }
}

// handle video can play
function handleVideoCanPlay() {
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.style.opacity = '0'
    }
}

// handle stalled video (network issues or something wrong with resource streaming)
function handleVideoStalled(video) {
    console.warn('Video stalled, attempting to recover...')
    const statusElement = document.getElementById('video-status')
    if (statusElement) {
        statusElement.textContent = 'Connection issues, retrying...'
        statusElement.style.opacity = '1'
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
            statusElement.textContent = 'Seeking...'
            statusElement.style.opacity = '1'
        }
    })

    video.addEventListener('seeked', function () {
        const statusElement = document.getElementById('video-status')
        if (statusElement) {
            statusElement.style.opacity = '0'
        }
    })
}

function initializeVideoControls() {
    const video = document.getElementById('main-video')
    const controls = document.getElementById('custom-video-controls')
    if (!video || !controls) {
        return
    }

    const playButton = controls.querySelector('[data-action="toggle-play"]')
    const fullscreenButton = controls.querySelector('[data-action="toggle-fullscreen"]')
    const progress = document.getElementById('video-progress')
    const currentTimeElement = document.getElementById('video-current-time')
    const totalDurationElement = document.getElementById('video-total-duration')
    const volumeSlider = document.getElementById('video-volume')

    if (!playButton || !progress || !currentTimeElement || !totalDurationElement || !volumeSlider) {
        return
    }

    const updatePlayButton = () => {
        const icon = playButton.querySelector('i')
        if (!icon) {
            return
        }
        if (video.paused) {
            icon.classList.remove('fa-pause')
            icon.classList.add('fa-play')
        } else {
            icon.classList.remove('fa-play')
            icon.classList.add('fa-pause')
        }
    }

    const updateProgress = () => {
        if (!video.duration || isNaN(video.duration)) {
            return
        }
        const percentage = (video.currentTime / video.duration) * 100
        progress.value = percentage
        controls.style.setProperty('--video-progress', percentage)
        currentTimeElement.textContent = formatDuration(video.currentTime)
        totalDurationElement.textContent = formatDuration(video.duration)
    }

    const syncVolumeSlider = () => {
        volumeSlider.value = video.muted ? 0 : video.volume
    }

    const togglePlayback = () => {
        if (video.paused) {
            video.play()
        } else {
            video.pause()
        }
    }

    playButton.addEventListener('click', togglePlayback)
    video.addEventListener('click', togglePlayback)

    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', () => toggleVideoFullscreen())
    }

    progress.addEventListener('input', (event) => {
        if (!video.duration || isNaN(video.duration)) {
            return
        }
        const percentValue = parseFloat(event.target.value)
        const percent = percentValue / 100
        video.currentTime = percent * video.duration
        controls.style.setProperty('--video-progress', percentValue)
    })

    volumeSlider.addEventListener('input', (event) => {
        const value = parseFloat(event.target.value)
        video.volume = value
        video.muted = value === 0
    })

    video.addEventListener('timeupdate', updateProgress)
    video.addEventListener('loadedmetadata', updateProgress)
    video.addEventListener('play', updatePlayButton)
    video.addEventListener('pause', updatePlayButton)
    video.addEventListener('volumechange', syncVolumeSlider)

    // initialize state
    updatePlayButton()
    updateProgress()
    syncVolumeSlider()
}

// -----------------------------
// AUDIO PLAYER FUNCTIONALITY
// -----------------------------
// update audio details
function updateAudioInfo(audio) {
    const durationElement = document.getElementById('audio-duration')
    const headerDurationElement = document.getElementById('audio-file-duration')
    const controlsDurationElement = document.getElementById('audio-total-duration')
    if (audio.duration && !isNaN(audio.duration)) {
        const duration = formatDuration(audio.duration)
        if (durationElement) {
            durationElement.textContent = `Duration: ${duration}`
        }
        if (headerDurationElement) {
            headerDurationElement.textContent = duration
        }
        if (controlsDurationElement) {
            controlsDurationElement.textContent = duration
        }
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

function initializeAudioControls() {
    const audio = document.getElementById('main-audio')
    const controls = document.getElementById('custom-audio-controls')

    if (!audio || !controls) {
        return
    }

    const playButton = controls.querySelector('[data-action="toggle-play"]')
    const progress = document.getElementById('audio-progress')
    const currentTimeElement = document.getElementById('audio-current-time')
    const totalDurationElement = document.getElementById('audio-total-duration')
    const volumeSlider = document.getElementById('audio-volume')

    if (!playButton || !progress || !currentTimeElement || !totalDurationElement || !volumeSlider) {
        return
    }

    const updatePlayButton = () => {
        const icon = playButton.querySelector('i')
        if (!icon) {
            return
        }
        if (audio.paused) {
            icon.classList.remove('fa-pause')
            icon.classList.add('fa-play')
        } else {
            icon.classList.remove('fa-play')
            icon.classList.add('fa-pause')
        }
    }

    const updateProgress = () => {
        if (!audio.duration || isNaN(audio.duration)) {
            return
        }
        const percentage = (audio.currentTime / audio.duration) * 100
        progress.value = percentage
        controls.style.setProperty('--audio-progress', percentage)
        currentTimeElement.textContent = formatDuration(audio.currentTime)
        totalDurationElement.textContent = formatDuration(audio.duration)
    }

    const syncVolumeSlider = () => {
        volumeSlider.value = audio.muted ? 0 : audio.volume
    }

    playButton.addEventListener('click', () => {
        if (audio.paused) {
            audio.play()
        } else {
            audio.pause()
        }
    })

    progress.addEventListener('input', (event) => {
        if (!audio.duration || isNaN(audio.duration)) {
            return
        }
        const percentValue = parseFloat(event.target.value)
        const percent = percentValue / 100
        audio.currentTime = percent * audio.duration
        controls.style.setProperty('--audio-progress', percentValue)
    })

    volumeSlider.addEventListener('input', (event) => {
        const value = parseFloat(event.target.value)
        audio.volume = value
        audio.muted = value === 0
    })

    audio.addEventListener('timeupdate', updateProgress)
    audio.addEventListener('loadedmetadata', updateProgress)
    audio.addEventListener('play', updatePlayButton)
    audio.addEventListener('pause', updatePlayButton)
    audio.addEventListener('volumechange', syncVolumeSlider)

    // initialize state
    updatePlayButton()
    updateProgress()
    syncVolumeSlider()
}

function seekMedia(mediaElement, offsetSeconds) {
    if (!mediaElement || !mediaElement.duration || isNaN(mediaElement.duration)) {
        return false
    }

    const newTime = Math.max(0, Math.min(mediaElement.duration, mediaElement.currentTime + offsetSeconds))
    mediaElement.currentTime = newTime
    return true
}

// -----------------------------
// GLOBAL EVENT LISTENERS
// -----------------------------
document.addEventListener('keydown', function (e) {
    // esc key - exit fullscreen or reset zoom
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
    const activeElement = document.activeElement
    const isFormFocus = activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT' || activeElement.isContentEditable)
    const isArrowKey = e.key === 'ArrowLeft' || e.key === 'ArrowRight'
    if (isArrowKey && !isFormFocus) {
        const mediaTarget = document.getElementById('main-video') || document.getElementById('main-audio')
        if (mediaTarget && seekMedia(mediaTarget, e.key === 'ArrowLeft' ? -5 : 5)) {
            e.preventDefault()
            return
        }
    }

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

// -----------------------------
// GLOBAL EXPOSURE
// -----------------------------
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
