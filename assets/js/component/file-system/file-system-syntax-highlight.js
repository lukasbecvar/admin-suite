/** file-system syntax highlighting functionality */
document.addEventListener('DOMContentLoaded', function() {
    // import highlight.js core
    const hljs = require('highlight.js/lib/core')

    // import common languages
    hljs.registerLanguage('xml', require('highlight.js/lib/languages/xml'))
    hljs.registerLanguage('css', require('highlight.js/lib/languages/css'))
    hljs.registerLanguage('php', require('highlight.js/lib/languages/php'))
    hljs.registerLanguage('sql', require('highlight.js/lib/languages/sql'))
    hljs.registerLanguage('ini', require('highlight.js/lib/languages/ini'))
    hljs.registerLanguage('bash', require('highlight.js/lib/languages/bash'))
    hljs.registerLanguage('json', require('highlight.js/lib/languages/json'))
    hljs.registerLanguage('yaml', require('highlight.js/lib/languages/yaml'))
    hljs.registerLanguage('nginx', require('highlight.js/lib/languages/nginx'))
    hljs.registerLanguage('apache', require('highlight.js/lib/languages/apache'))
    hljs.registerLanguage('python', require('highlight.js/lib/languages/python'))
    hljs.registerLanguage('markdown', require('highlight.js/lib/languages/markdown'))
    hljs.registerLanguage('javascript', require('highlight.js/lib/languages/javascript'))
    hljs.registerLanguage('dockerfile', require('highlight.js/lib/languages/dockerfile'))

    // register custom language for logs and general text files
    hljs.registerLanguage('general-log', function() {
        return {
            name: 'General Log',
            case_insensitive: true,
            contains: [
                // timestamps in various formats
                {
                    className: 'number',
                    begin: /\b\d{4}[-/]\d{1,2}[-/]\d{1,2}\b|\b\d{1,2}:\d{2}(:\d{2})?(\.\d+)?\b|\b\d{10,13}\b/
                },
                // ip addresses
                {
                    className: 'number',
                    begin: /\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/
                },
                // brackets, parentheses, and braces
                {
                    className: 'meta',
                    begin: /[\[\](){}<>]/
                },
                // quoted strings
                {
                    className: 'string',
                    begin: /"/, end: /"/
                },
                {
                    className: 'string',
                    begin: /'/, end: /'/
                },
                // error, warning, info keywords
                {
                    className: 'keyword',
                    begin: /\b(ERROR|Error|error|WARN|Warn|warn|WARNING|Warning|warning|INFO|Info|info|DEBUG|Debug|debug|NOTICE|Notice|notice|CRITICAL|Critical|critical|ALERT|Alert|alert|EMERGENCY|Emergency|emergency)\b/
                },
                // http methods and status codes
                {
                    className: 'keyword',
                    begin: /\b(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\b|\b([1-5][0-9][0-9])\b/
                },
                // file paths
                {
                    className: 'string',
                    begin: /\/[\w\-.\/]+/
                },
                // numbers
                {
                    className: 'number',
                    begin: /\b\d+\b/
                },
                // urls
                {
                    className: 'string',
                    begin: /(https?|ftp|file):\/\/[-A-Za-z0-9+&@#/%?=~_|!:,.;]+[-A-Za-z0-9+&@#/%=~_|]/
                }
            ]
        }
    })

    // get element containing file content
    const codeElement = document.querySelector('pre.file-content')

    if (codeElement) {
        // get file path from data attribute
        const filePath = codeElement.getAttribute('data-path')

        if (filePath) {
            // detect language based on file extension
            const language = detectLanguage(filePath)

            if (language) {
                // add language class to the pre element
                codeElement.classList.add(`language-${language}`)

                // apply syntax highlighting
                hljs.highlightElement(codeElement)
            } else {
                // apply general log highlighting for files without a specific language
                applyGeneralHighlighting(codeElement)
            }
        } else {
            // apply general log highlighting if no file path is available
            applyGeneralHighlighting(codeElement)
        }
    }

    // apply general highlighting to a code element
    function applyGeneralHighlighting(element) {
        // add general-log class
        element.classList.add('language-general-log')

        // apply highlighting
        hljs.highlightElement(element)
    }

    // detect language based on file extension
    function detectLanguage(filePath) {
        // extract file extension
        const extension = filePath.split('.').pop().toLowerCase()

        // map file extensions to highlight.js languages
        const extensionMap = {
            'dockerfile': 'dockerfile',
            'access': 'general-log',
            'markdown': 'markdown',
            'error': 'general-log',
            'debug': 'general-log',
            'dockerignore': 'bash',
            'editorconfig': 'ini',
            'logs': 'general-log',
            'htaccess': 'apache',
            'err': 'general-log',
            'log': 'general-log',
            'gitignore': 'bash',
            'jsx': 'javascript',
            'tsx': 'javascript',
            'js': 'javascript',
            'ts': 'javascript',
            'nginx': 'nginx',
            'md': 'markdown',
            'swift': 'swift',
            'conf': 'apache',
            'config': 'ini',
            'bash': 'bash',
            'json': 'json',
            'yaml': 'yaml',
            'py': 'python',
            'java': 'java',
            'cs': 'csharp',
            'yml': 'yaml',
            'toml': 'ini',
            'zsh': 'bash',
            'env': 'bash',
            'html': 'xml',
            'scss': 'css',
            'sass': 'css',
            'less': 'css',
            'ini': 'ini',
            'htm': 'xml',
            'xml': 'xml',
            'css': 'css',
            'php': 'php',
            'rb': 'ruby',
            'cpp': 'cpp',
            'rs': 'rust',
            'sh': 'bash',
            'sql': 'sql',
            'go': 'go',
            'c': 'c'
        }

        // check if the extension is in the map
        if (extension in extensionMap) {
            return extensionMap[extension]
        }

        // check for special filenames without extensions
        const fileName = filePath.split('/').pop().toLowerCase()
        const specialFiles = {
            'docker-compose.yaml': 'yaml',
            'docker-compose.yml': 'yaml',
            'dockerfile': 'dockerfile',
            '.dockerignore': 'bash',
            'jenkinsfile': 'groovy',
            'makefile': 'makefile',
            '.editorconfig': 'ini',
            '.gitignore': 'bash',
            '.env': 'bash'
        }

        if (fileName in specialFiles) {
            return specialFiles[fileName]
        }

        // check for common log file patterns in the filename
        if (fileName.includes('log') ||
            fileName.includes('error') ||
            fileName.includes('debug') ||
            fileName.includes('access') ||
            fileName.includes('journal') ||
            fileName.includes('syslog') ||
            fileName.includes('messages')) {
            return 'general-log'
        }

        // no matching language found
        return null
    }
})
