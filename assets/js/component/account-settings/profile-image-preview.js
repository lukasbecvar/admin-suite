/** profile image preview */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // IMAGE INPUT EVENT HANDLER
    // -----------------------------
    const input = document.getElementById('profile-pic-input')

    // check if input exists
    if (!input) {
        console.warn('Profile picture input not found')
        return
    }

    input.addEventListener('change', () => {
        validateAndPreviewImage(input)
    })
})

// -----------------------------
// IMAGE VALIDATION AND PREVIEW FUNCTION
// -----------------------------
function validateAndPreviewImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0]
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
        const maxSize = 5 * 1024 * 1024 // 5MB limit

        // check file type
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP).')
            input.value = ''
            document.getElementById('image-preview-container').style.display = 'none'
            return
        }

        // check file size
        if (file.size > maxSize) {
            alert('File size must be less than 5MB.')
            input.value = ''
            document.getElementById('image-preview-container').style.display = 'none'
            return
        }

        // show preview if validation passes
        const reader = new FileReader()
        reader.onload = (e) => {
            document.getElementById('preview-image').setAttribute('src', e.target.result)
            document.getElementById('image-preview-container').style.display = 'block'
        }
        reader.readAsDataURL(file)
    } else {
        document.getElementById('image-preview-container').style.display = 'none'
    }
}
