/** file-system upload component functionality */
document.addEventListener('DOMContentLoaded', function() {

    // global variables
    let selectedFiles = []
    let isUploading = false
    let uploadStats = {
        startTime: null,
        totalBytes: 0,
        uploadedBytes: 0
    }

    // configuration - will be set from template via window object
    window.uploadConfig = window.uploadConfig || {
        chunkUploadUrl: '',
        redirectUrl: ''
    }

    // handle file selection
    function handleFileSelection(input) {
        if (isUploading) return

        // add new files to existing selection
        const newFiles = Array.from(input.files)
        selectedFiles = [...selectedFiles, ...newFiles]
        updateFileList()

        // clear the input so same files can be selected again
        input.value = ''
    }

    // update file list
    function updateFileList() {
        const fileList = document.getElementById('file-list')
        const fileListContent = document.getElementById('file-list-content')
        const uploadButton = document.getElementById('upload-button')
        
        if (selectedFiles.length > 0) {
            fileList.classList.remove('hidden')
            fileListContent.innerHTML = ''
            uploadStats.totalBytes = selectedFiles.reduce((total, file) => total + file.size, 0)
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div')
                fileItem.className = 'flex items-center justify-between bg-gray-700/30 rounded px-3 py-2 text-sm border border-gray-600/30'
                fileItem.id = `file-item-${index}`
                const fileIcon = getFileIcon(file.name)
                const fileSize = formatFileSize(file.size)
                const isLargeFile = file.size > 10 * 1024 * 1024 // 10MB
                
                fileItem.innerHTML = `
                    <div class="flex items-center gap-3 flex-1">
                        <i class="${fileIcon} text-gray-400"></i>
                        <div class="flex-1 min-w-0">
                            <div class="text-gray-200 truncate font-medium">${file.name}</div>
                            <div class="text-gray-400 text-xs flex items-center gap-2">
                                <span>${fileSize}</span>
                                ${isLargeFile ? '<span class="text-blue-400"><i class="fas fa-layer-group mr-1"></i>Chunked</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-16 h-1 bg-gray-600/50 rounded-full hidden" id="progress-${index}">
                            <div class="h-1 bg-blue-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <i class="fas text-gray-500 text-xs hidden" id="status-${index}"></i>
                        <button onclick="removeFile(${index})" class="w-6 h-6 bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 rounded flex items-center justify-center transition-all duration-200" title="Remove file" id="remove-${index}">
                            <i class="fas fa-times text-red-400 text-xs"></i>
                        </button>
                    </div>
                `
                fileListContent.appendChild(fileItem)
            })
            
            uploadButton.disabled = false
        } else {
            fileList.classList.add('hidden')
            uploadButton.disabled = true
        }
    }

    // get file icon based on extension
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase()
        const iconMap = {
            'txt': 'fas fa-file-alt',
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word', 'docx': 'fas fa-file-word',
            'xls': 'fas fa-file-excel', 'xlsx': 'fas fa-file-excel',
            'ppt': 'fas fa-file-powerpoint', 'pptx': 'fas fa-file-powerpoint',
            'zip': 'fas fa-file-archive', 'rar': 'fas fa-file-archive', '7z': 'fas fa-file-archive',
            'jpg': 'fas fa-file-image', 'jpeg': 'fas fa-file-image', 'png': 'fas fa-file-image', 'gif': 'fas fa-file-image',
            'mp4': 'fas fa-file-video', 'avi': 'fas fa-file-video', 'mov': 'fas fa-file-video',
            'mp3': 'fas fa-file-audio', 'wav': 'fas fa-file-audio',
            'js': 'fas fa-file-code', 'html': 'fas fa-file-code', 'css': 'fas fa-file-code', 'php': 'fas fa-file-code', 'py': 'fas fa-file-code'
        }
        return iconMap[ext] || 'fas fa-file'
    }

    // remove file from list
    function removeFile(index) {
        if (isUploading) return
        selectedFiles.splice(index, 1)
        updateFileList()
    }

    // format file size to readable format
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes'
        const k = 1024
        const sizes = ['Bytes', 'KB', 'MB', 'GB']
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    // format upload speed to readable format
    function formatSpeed(bytesPerSecond) {
        if (bytesPerSecond === 0) return '0 B/s'
        const k = 1024
        const sizes = ['B/s', 'KB/s', 'MB/s', 'GB/s']
        const i = Math.floor(Math.log(bytesPerSecond) / Math.log(k))
        return parseFloat((bytesPerSecond / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
    }

    // start upload process
    async function startUpload() {
        if (isUploading || selectedFiles.length === 0) return
        
        isUploading = true
        uploadStats.startTime = Date.now()
        uploadStats.uploadedBytes = 0
        uploadStats.totalBytes = selectedFiles.reduce((total, file) => total + file.size, 0)
        
        // disable file input but keep scrolling enabled
        const fileInput = document.getElementById('file-input')
        const fileInputLabel = document.getElementById('file-input-label')
        
        fileInput.disabled = true
        fileInputLabel.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none')
        fileInputLabel.classList.remove('hover:bg-blue-500/30', 'cursor-pointer')
        
        // show progress section
        document.getElementById('upload-progress').classList.remove('hidden')
        document.getElementById('upload-button').disabled = true
        document.getElementById('cancel-button').style.display = 'none'
        document.getElementById('upload-button').style.display = 'none'
        
        const progressContent = document.getElementById('progress-content')
        progressContent.innerHTML = ''
        
        // initialize overall progress
        updateOverallProgress()
        
        let successCount = 0
        let errorCount = 0
        
        // disable remove buttons and show waiting icons
        for (let i = 0; i < selectedFiles.length; i++) {
            const statusIcon = document.getElementById(`status-${i}`)
            const removeButton = document.getElementById(`remove-${i}`)
            
            // show waiting icon and disable remove button
            statusIcon.className = 'fas fa-clock text-yellow-400 text-xs'
            statusIcon.classList.remove('hidden')
            removeButton.disabled = true
            removeButton.classList.add('opacity-50', 'cursor-not-allowed')
        }
        
        for (let i = 0; i < selectedFiles.length; i++) {
            const file = selectedFiles[i]
            const statusIcon = document.getElementById(`status-${i}`)
            const progressBar = document.getElementById(`progress-${i}`)
            
            // show progress bar and reset to 0%, change to spinner
            progressBar.classList.remove('hidden')
            const progressFill = progressBar.querySelector('div')
            progressFill.style.width = '0%'
            statusIcon.className = 'fas fa-spinner fa-spin text-blue-400 text-xs'
            
            try {
                const result = await uploadFile(file, i)
                if (result.success) {
                    statusIcon.className = 'fas fa-check text-green-400 text-xs'
                    successCount++
                    // ensure progress is at 100% for completed files
                    updateFileProgress(i, 100)
                    updateOverallProgress()
                } else {
                    statusIcon.className = 'fas fa-times text-red-400 text-xs'
                    errorCount++
                    
                    // add error message
                    const errorDiv = document.createElement('div')
                    errorDiv.className = 'text-red-400 text-sm bg-red-500/10 border border-red-500/30 rounded px-3 py-2'
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${file.name}: ${result.error}`
                    progressContent.appendChild(errorDiv)
                }
            } catch (error) {
                statusIcon.className = 'fas fa-times text-red-400 text-xs'
                errorCount++
                
                const errorDiv = document.createElement('div')
                errorDiv.className = 'text-red-400 text-sm bg-red-500/10 border border-red-500/30 rounded px-3 py-2'
                errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${file.name}: Upload failed`
                progressContent.appendChild(errorDiv)
            }
        }
        
        // final progress update to ensure 100%
        document.getElementById('overall-progress-bar').style.width = '100%'
        document.getElementById('progress-text').textContent = '100% complete'
        
        // show completion message
        const completionDiv = document.createElement('div')
        if (successCount > 0) {
            completionDiv.className = 'text-green-400 text-sm bg-green-500/10 border border-green-500/30 rounded px-3 py-2'
            completionDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i>${successCount} file(s) uploaded successfully`
            progressContent.appendChild(completionDiv)
        }
        
        // auto redirect after 3 seconds
        setTimeout(() => {
            window.location.href = window.uploadConfig.redirectUrl
        }, 3000)
    }

    // expose functions globally for template access
    window.startUpload = startUpload

    async function uploadFile(file, fileIndex) {
        const chunkSize = 1024 * 1024 // 1MB chunks
        const totalChunks = Math.ceil(file.size / chunkSize)
        const fileId = generateFileId()
        const directoryPath = document.getElementById('directory-input').value

        // for small files, use single upload
        if (file.size <= chunkSize) {
            const result = await uploadSingleFile(file, fileIndex, directoryPath)
            if (result.success) {
                updateFileProgress(fileIndex, 100)
                updateOverallProgress()
            }
            return result
        }

        // for large files, use chunked upload
        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            const start = chunkIndex * chunkSize
            const end = Math.min(start + chunkSize, file.size)
            const chunk = file.slice(start, end)

            const formData = new FormData()
            formData.append('chunk', chunk)
            formData.append('filename', file.name)
            formData.append('directory', directoryPath)
            formData.append('chunkIndex', chunkIndex)
            formData.append('totalChunks', totalChunks)
            formData.append('fileId', fileId)

            try {
                // send chunked upload request
                const response = await fetch(window.uploadConfig.chunkUploadUrl, {
                    method: 'POST',
                    body: formData
                })

                // check response status
                const result = await response.json()

                if (!result.success) {
                    return { success: false, error: result.error }
                }

                // update progress based on chunks completed
                const progress = ((chunkIndex + 1) / totalChunks) * 100
                updateFileProgress(fileIndex, progress)
                updateOverallProgress()

                // small delay to allow UI updates
                await new Promise(resolve => setTimeout(resolve, 10))

            } catch (error) {
                return { success: false, error: 'Network error during upload' }
            }
        }

        return { success: true }
    }

    // upload small files in a single request
    async function uploadSingleFile(file, fileIndex, directoryPath) {
        const formData = new FormData()
        formData.append('chunk', file)
        formData.append('filename', file.name)
        formData.append('directory', directoryPath)
        formData.append('chunkIndex', 0)
        formData.append('totalChunks', 1)
        formData.append('fileId', generateFileId())

        try {
            // send single upload request
            const response = await fetch(window.uploadConfig.chunkUploadUrl, {
                method: 'POST',
                body: formData
            })

            // check response status
            const result = await response.json()

            // check response status
            if (result.success) {
                updateFileProgress(fileIndex, 100)
                updateOverallProgress()
                return { success: true }
            } else {
                return { success: false, error: result.error }
            }
        } catch (error) {
            return { success: false, error: 'Network error during upload' }
        }
    }

    // update progress bar for individual file
    function updateFileProgress(fileIndex, progress) {
        const progressBar = document.getElementById(`progress-${fileIndex}`)
        if (progressBar) {
            const progressFill = progressBar.querySelector('div')
            progressFill.style.width = progress + '%'
        }
    }

    // update overall progress bar
    function updateOverallProgress() {
        // calculate progress based on actual bytes uploaded
        let totalUploadedBytes = 0

        selectedFiles.forEach((file, index) => {
            const progressBar = document.getElementById(`progress-${index}`)
            if (progressBar) {
                const progressFill = progressBar.querySelector('div')
                const progressPercent = parseFloat(progressFill.style.width) || 0
                totalUploadedBytes += (file.size * progressPercent / 100)
            }
        })

        const overallProgress = uploadStats.totalBytes > 0 ? (totalUploadedBytes / uploadStats.totalBytes) * 100 : 0
        const progressBar = document.getElementById('overall-progress-bar')
        const progressText = document.getElementById('progress-text')
        const speedText = document.getElementById('upload-speed')

        progressBar.style.width = Math.min(100, overallProgress) + '%'
        progressText.textContent = Math.round(Math.min(100, overallProgress)) + '% complete'

        // update uploaded bytes for speed calculation
        uploadStats.uploadedBytes = totalUploadedBytes

        // calculate upload speed
        if (uploadStats.startTime && totalUploadedBytes > 0) {
            const elapsed = (Date.now() - uploadStats.startTime) / 1000
            const speed = totalUploadedBytes / elapsed
            speedText.textContent = formatSpeed(speed)
        } else {
            speedText.textContent = '0 B/s'
        }
    }

    // generate unique file id
    function generateFileId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2)
    }

    // drag and drop functionality
    function initializeDragAndDrop() {
        const uploadArea = document.getElementById('upload-area')
        const fileInput = document.getElementById('file-input')

        if (!uploadArea || !fileInput) {
            console.error('Upload area or file input not found')
            return
        }

        function preventDefaults(e) {
            e.preventDefault()
            e.stopPropagation()
        }

        function highlight(e) {
            if (!isUploading) {
                uploadArea.classList.add('border-blue-500', 'bg-blue-500/10')
            }
        }

        function unhighlight(e) {
            if (!isUploading) {
                uploadArea.classList.remove('border-blue-500', 'bg-blue-500/10')
            }
        }

        function handleDrop(e) {
            if (isUploading) {
                e.preventDefault()
                return
            }

            const dt = e.dataTransfer
            const files = dt.files

            // add new files to existing selection instead of replacing
            const newFiles = Array.from(files)
            selectedFiles = [...selectedFiles, ...newFiles]
            updateFileList()
        }

        // register all event listeners
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false)
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false)
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false)
        });

        uploadArea.addEventListener('drop', handleDrop, false)
    }

    // initialize drag and drop
    initializeDragAndDrop()

    // also add global drag and drop prevention to avoid browser opening files
    document.addEventListener('dragover', function(e) {
        e.preventDefault()
    })

    document.addEventListener('drop', function(e) {
        e.preventDefault()
    })

    // expose functions globally for template access
    window.handleFileSelection = handleFileSelection

    // expose functions globally for template access
    window.removeFile = removeFile
})
