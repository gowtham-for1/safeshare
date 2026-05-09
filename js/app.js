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
    const fileIcon = document.getElementById('fileIcon');

    if (!dropZone || !fileInput) return;
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    const audioPreview = document.getElementById('audioPreview');
    let currentObjectURL = null;

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

        if (currentObjectURL) {
            URL.revokeObjectURL(currentObjectURL);
        }

        if (file.type.startsWith('image/') || file.type.startsWith('video/') || file.type.startsWith('audio/')) {
            currentObjectURL = URL.createObjectURL(file);
            if (file.type.startsWith('image/')) {
                imagePreview.src = currentObjectURL;
                imagePreview.classList.remove('hidden');
            } else if (file.type.startsWith('video/')) {
                videoPreview.src = currentObjectURL;
                videoPreview.classList.remove('hidden');
            } else if (file.type.startsWith('audio/')) {
                audioPreview.src = currentObjectURL;
                audioPreview.classList.remove('hidden');
            }
        } else {
            fileIcon.classList.remove('hidden'); // Show generic icon for other types
        }
    }

    // ── AJAX Upload with Progress ──
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const file = fileInput.files[0];
        if (!file) return;

        progressBar.classList.remove('hidden');
        btnText.classList.add('hidden');
        btnSpinner.classList.remove('hidden');

        const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const fileId = Math.random().toString(36).substring(2) + Date.now();

        uploadChunk(0);

        function uploadChunk(chunkIndex) {
            const start = chunkIndex * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('file', chunk);
            formData.append('chunk_index', chunkIndex);
            formData.append('total_chunks', totalChunks);
            formData.append('file_id', fileId);
            formData.append('file_name', file.name);
            
            // Pass other form fields only on the first or last chunk
            if (chunkIndex === totalChunks - 1) {
                formData.append('expiry', uploadForm.expiry.value);
                formData.append('max_downloads', uploadForm.max_downloads.value);
                formData.append('password', uploadForm.password.value);
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const totalUploaded = (chunkIndex * CHUNK_SIZE) + e.loaded;
                    const percent = Math.min(Math.round((totalUploaded / file.size) * 100), 100);
                    progressFill.style.width = percent + '%';
                    progressPct.textContent = percent + '%';
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    if (chunkIndex < totalChunks - 1) {
                        uploadChunk(chunkIndex + 1);
                    } else {
                        // Final chunk uploaded, server returns final redirect
                        const response = JSON.parse(xhr.responseText);
                        window.location.href = response.redirect || 'success.php';
                    }
                } else {
                    alert("Upload failed at chunk " + (chunkIndex + 1));
                }
            };

            xhr.send(formData);
        }
    });
});
