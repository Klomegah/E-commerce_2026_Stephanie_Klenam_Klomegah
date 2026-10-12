/* js/confirm.js - a styled replacement for window.confirm().

   Any form carrying data-confirm="message" asks the question in a modal
   built from HTML + CSS instead of the browser's plain pop-up. The form is
   only submitted when the user presses the confirm button, so the guard
   still holds for anyone who never sees the dialog. */

document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-confirm]');

    if (forms.length === 0) {
        return;
    }

    // Built once and reused for every form on the page.
    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.hidden = true;
    overlay.innerHTML =
        '<div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title">' +
            '<h3 class="confirm-title" id="confirm-title">Please confirm</h3>' +
            '<p class="confirm-message"></p>' +
            '<div class="confirm-actions">' +
                '<button type="button" class="btn btn-cancel">Cancel</button>' +
                '<button type="button" class="btn btn-danger">Delete</button>' +
            '</div>' +
        '</div>';

    document.body.appendChild(overlay);

    var titleEl = overlay.querySelector('.confirm-title');
    var messageEl = overlay.querySelector('.confirm-message');
    var confirmBtn = overlay.querySelector('.btn-danger');
    var cancelBtn = overlay.querySelector('.btn-cancel');
    var pendingForm = null;
    var lastFocused = null;

    function close() {
        overlay.hidden = true;
        pendingForm = null;

        if (lastFocused) {
            lastFocused.focus();
        }
    }

    function open(form) {
        pendingForm = form;
        lastFocused = document.activeElement;

        titleEl.textContent = form.getAttribute('data-confirm-title') || 'Please confirm';
        messageEl.textContent = form.getAttribute('data-confirm');
        confirmBtn.textContent = form.getAttribute('data-confirm-label') || 'Delete';

        overlay.hidden = false;
        cancelBtn.focus();
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // The confirm button calls form.submit(), which fires no submit
            // event, so there is no loop back into this handler.
            event.preventDefault();
            open(form);
        });
    });

    confirmBtn.addEventListener('click', function () {
        if (!pendingForm) {
            return;
        }

        var form = pendingForm;
        overlay.hidden = true;
        pendingForm = null;
        form.submit();
    });

    cancelBtn.addEventListener('click', close);

    // Clicking the dark backdrop (but not the dialog itself) cancels.
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            close();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) {
            close();
        }
    });
});
