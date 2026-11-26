const photoInput = document.getElementById('report-photo');
const photoPreview = document.getElementById('photo-preview');
let previewUrl;

photoInput.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    photoPreview.hidden = true;
    photoPreview.removeAttribute('src');
    const file = photoInput.files[0];
    if (file && ['image/jpeg', 'image/png'].includes(file.type) && file.size <= 5 * 1024 * 1024) {
        previewUrl = URL.createObjectURL(file);
        photoPreview.src = previewUrl;
        photoPreview.hidden = false;
    }
});
