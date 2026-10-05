/* js admin.js - client-side checks for the admin product form. */

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('product-form');

    if (!form) {
        return;
    }

    var imageInput = document.getElementById('product_image');

    if (!imageInput) {
        return;
    }

    var MAX_BYTES = 2 * 1024 * 1024;

    var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    var ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    var errorSpan = document.getElementById('err-product_image');

    function setImageError(message) {
        if (errorSpan) {
            errorSpan.textContent = message;
        }
        imageInput.classList.toggle('invalid', message !== '');
    }

    function checkImage(file) {
        if (!file) {
            setImageError('');
            return true;
        }

        if (ALLOWED_TYPES.indexOf(file.type) === -1) {
            setImageError('Choose a JPEG, PNG, GIF or WEBP image.');
            return false;
        }

        if (file.size > MAX_BYTES) {
            setImageError('Image must be smaller than 2 MB.');
            return false;
        }

        var parts = file.name.split('.');
        var extension = (parts.length > 1 ? parts[parts.length - 1] : '').toLowerCase();

        if (ALLOWED_EXTENSIONS.indexOf(extension) === -1) {
            setImageError('That file does not end in ' + ALLOWED_EXTENSIONS.join(', ') + '.');
            return false;
        }

        setImageError('');
        return true;
    }

    imageInput.addEventListener('change', function () {
        checkImage(imageInput.files[0]);
    });

    form.addEventListener('submit', function (event) {
        if (!checkImage(imageInput.files[0])) {
            event.preventDefault();
            imageInput.focus();
        }
    });
});
