/**
 * app.js — Client-side logic for SafeShare Pro
 */

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const dropContent = document.getElementById('dropContent');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    const progressPct = document.getElementById('progressPct');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    if (!dropZone || !fileInput) return;

    // ── Drag & Drop Handlers ──
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('active');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('active');
        }, false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect(files[0]);
    }, false);

    // Handle browse selection
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            handleFileSelect(this.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!file) return;
        dropContent.classList.add('hidden');
        filePreview.classList.remove('hidden');
        fileName.textContent = file.name;
        
        // Format size
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileSize.textContent = sizeMB + ' MB';
    }

    // ── AJAX Upload with Progress ──
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();

        progressBar.classList.remove('hidden');
        btnText.classList.add('hidden');
        btnSpinner.classList.remove('hidden');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressPct.textContent = percent + '%';
            }
        });

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                // Whether success or error (redirect), we move to the response URL
                window.location.href = xhr.responseURL || 'success.php';
            }
        };

        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    });
});
