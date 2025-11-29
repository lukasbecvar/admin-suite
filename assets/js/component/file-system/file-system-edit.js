/** file-system edit component functionality */
document.addEventListener('DOMContentLoaded', function()
{
    const editor = document.getElementById('editor')

    // -----------------------------
    // EDITOR FUNCTIONALITY
    // -----------------------------
    // enable tab key in textarea
    editor.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault()

            // get cursor position
            const start = this.selectionStart
            const end = this.selectionEnd

            // insert tab at cursor position
            this.value = this.value.substring(0, start) + '    ' + this.value.substring(end)

            // move cursor after tab
            this.selectionStart = this.selectionEnd = start + 4
        }
    })

    // -----------------------------
    // KEYBOARD SHORTCUTS
    // -----------------------------
    // ctrl+s to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault()
            document.querySelector('form').submit()
        }
    })

    // -----------------------------
    // INITIALIZATION
    // -----------------------------
    // focus editor
    editor.focus()
})
