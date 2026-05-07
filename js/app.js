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
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    const audioPreview = document.getElementById('audioPreview');

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

        // Client-side size validation
        if (typeof MAX_FILE_SIZE_BYTES !== 'undefined' && file.size > MAX_FILE_SIZE_BYTES) {
            alert(`File size exceeds the maximum allowed limit of ${MAX_FILE_SIZE_HUMAN}. Please select a smaller file.`);
            fileInput.value = ''; // Clear the selected file
            // Reset preview if any
            dropContent.classList.remove('hidden');
            filePreview.classList.add('hidden');
            return;
        }

        filePreview.classList.remove('hidden');
        fileName.textContent = file.name;
        
        // Format size
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileSize.textContent = sizeMB + ' MB';

        // Reset all previews
        fileIcon.classList.add('hidden');
        imagePreview.classList.add('hidden');
        videoPreview.classList.add('hidden');
        audioPreview.classList.add('hidden');
        imagePreview.src = '#';
        videoPreview.src = '#';
        audioPreview.src = '#';

        const reader = new FileReader();
        reader.onloadend = function() {
            if (file.type.startsWith('image/')) {
                imagePreview.src = reader.result;
                imagePreview.classList.remove('hidden');
            } else if (file.type.startsWith('video/')) {
                videoPreview.src = reader.result;
                videoPreview.classList.remove('hidden');
            } else if (file.type.startsWith('audio/')) {
                audioPreview.src = reader.result;
                audioPreview.classList.remove('hidden');
            } else {
                fileIcon.classList.remove('hidden'); // Show generic icon for other types
            }
        };
        reader.readAsDataURL(file);
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
                if (xhr.status === 200) {
                    window.location.href = xhr.responseURL || 'success.php';
                } else if (xhr.status === 0) {
                    alert("Upload failed: Connection reset by server. The file might be larger than allowed by your php.ini settings (post_max_size or upload_max_filesize).");
                    btnText.classList.remove('hidden');
                    btnSpinner.classList.add('hidden');
                } else {
                    alert(`Upload failed (Status: ${xhr.status}). This could be a server error. Check your XAMPP Apache logs.`);
                    btnText.classList.remove('hidden');
                    btnSpinner.classList.add('hidden');
                }
            }
        };

        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    });
});
