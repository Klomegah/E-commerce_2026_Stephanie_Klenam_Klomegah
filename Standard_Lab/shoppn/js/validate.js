/**
 * js/validate.js - client-side form validation.
 * 
 */

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const phoneRegex = /^[0-9+\-\s]{7,15}$/;

// Mirrors the column limits in classes/CustomerClass.php.

const MAX = {
    name: 100,
    email: 50,
    country: 30,
    city: 30,
    contact: 15,
    pass: 6
};

/**
 * Show or clear the inline message for one field.
 */

function setError(input, message) {
    const span = document.getElementById('err-' + input.id);
    if (span) {
        span.textContent = message;
    }
    input.classList.toggle('invalid', message !== '');
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(el => (el.textContent = ''));
    form.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
}

/**
 * Validate one field. Returns true if it is acceptable.
 */

function validateField(input) {
    const value = input.value.trim();
    const name = input.name;

    // A select that was never chosen.
    if (input.tagName === 'SELECT' && value === '') {
        setError(input, 'Please choose an option.');
        return false;
    }

    if (input.type !== 'password' && input.type !== 'select-one' && value === '') {
        setError(input, 'This field is required.');
        return false;
    }

    if (name === 'customer_email') {
        if (value === '') {
            setError(input, 'Email is required.');
            return false;
        }
        if (!emailRegex.test(value)) {
            setError(input, 'Enter a valid email address.');
            return false;
        }
      

        if (value.length > MAX.email) {
            setError(input, 'Email must be ' + MAX.email + ' characters or fewer.');
            return false;
        }
    }

    if (name === 'customer_contact') {
        if (!phoneRegex.test(value)) {
            setError(input, 'Use 7-15 digits; spaces, + and - are allowed.');
            return false;
        }
    }

    if (name === 'customer_pass') {
        if (value.length < MAX.pass) {
            setError(input, 'Password must be at least ' + MAX.pass + ' characters.');
            return false;
        }
    }

    // Generic length check for the plain text fields.
    if (MAX[name] !== undefined && value.length > MAX[name]) {
        setError(input, 'Must be ' + MAX[name] + ' characters or fewer.');
        return false;
    }

    setError(input, '');
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form')
              || document.getElementById('login-form');

    if (!form) {
        return;
    }

    // Re-validate as the user leaves a field, so the message clears
    // itself the moment they fix the problem.

    form.querySelectorAll('input, select').forEach(function (input) {
        input.addEventListener('blur', function () {
            validateField(input);
        });
    });

    form.addEventListener('submit', function (event) {
        

        clearErrors(form);

        let valid = true;
        let firstBad = null;

        form.querySelectorAll('input, select').forEach(function (input) {
            if (!validateField(input)) {
                valid = false;
                if (firstBad === null) {
                    firstBad = input;
                }
            }
        });

        if (!valid) {
            // Stop the form actually being sent.

            event.preventDefault();
            if (firstBad) {
                firstBad.focus();
            }
            return;
        }

        // Optional loading state, so a slow server does not look broken.

        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Please wait...';
        }
    });
});
