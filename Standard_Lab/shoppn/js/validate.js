/* js validate.js - client-side form validation. */

const emailRegex = /^[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/;

const MAX = {
    name: 100,
    email: 50,
    country: 30,
    city: 30,
    contact: 15
};

let RULES = {
    password: {
        min: 8,
        max: 128,
        specials: '!@#$%^&*()-_=+[]{}<>?/\\|~.,;:',
        common: []
    },
    phone: { maxChars: 15, dialPlan: {} },
    hints: {}
};

function loadRules(form) {
    const raw = form.getAttribute('data-rules');

    if (!raw) {
        return;
    }

    try {
        const parsed = JSON.parse(raw);

        RULES = {
            password: Object.assign({}, RULES.password, parsed.password || {}),
            phone: Object.assign({}, RULES.phone, parsed.phone || {}),
            hints: parsed.hints || {}
        };
    } catch (e) {
    }
}


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

/* ---------------- password ----------------. */

/* Each rule is reported separately so the shopper can see WHICH conditions the password already meets. */
function checkPasswordRules(pass, email) {
    const rules = RULES.password;
    const specials = new Set((rules.specials || '').split(''));

    return {
        length: pass.length >= rules.min && pass.length <= rules.max,
        upper: /[A-Z]/.test(pass),
        lower: /[a-z]/.test(pass),
        number: /[0-9]/.test(pass),
        special: Array.from(pass).some(ch => specials.has(ch)),
        not_common: !(rules.common || []).includes(pass.toLowerCase()),
        not_sequential: !hasSequentialRun(pass),
        not_repeated: !/(.)\1{2,}/u.test(pass),
        not_email: !passwordEchoesEmail(pass, email)
    };
}

function hasSequentialRun(pass) {
    for (let i = 0; i <= pass.length - 4; i++) {
        const step = pass.charCodeAt(i + 1) - pass.charCodeAt(i);

        if (step !== 0 && Math.abs(step) === 1
            && pass.charCodeAt(i + 2) - pass.charCodeAt(i + 1) === step
            && pass.charCodeAt(i + 3) - pass.charCodeAt(i + 2) === step) {
            return true;
        }
    }

    return false;
}

/* True when the password is built out of the email's own name. */
function passwordEchoesEmail(pass, email) {
    if (!email || email.indexOf('@') === -1) {
        return false;
    }

    const local = email.split('@')[0];
    const bareLocal = local.toLowerCase().replace(/[^a-z0-9]/g, '');
    const barePass = pass.toLowerCase().replace(/[^a-z0-9]/g, '');

    if (bareLocal.length < 4) {
        return false;
    }

    return barePass.indexOf(bareLocal) !== -1;
}

/* Tick off the checklist under the password box as the shopper types, and move the strength bar. */
function updatePasswordChecklist(form, pass, email) {
    const results = checkPasswordRules(pass, email);
    const total = form.querySelectorAll('#password-rules li[data-rule]').length;

    // Three of the rules ("not a common password", "no repeats", "no.
    const blank = pass === '';
    let met = 0;

    form.querySelectorAll('#password-rules li[data-rule]').forEach(li => {
        const key = li.getAttribute('data-rule');
        const ok = !blank && results[key] === true;

        li.classList.toggle('met', ok);

        if (ok) {
            met++;
        }
    });

    if (total > 0) {
        const fill = document.getElementById('password-meter-fill');

        if (fill) {
            fill.style.width = Math.round((met / total) * 100) + '%';
            fill.classList.toggle('complete', met === total);
        }

        const counter = document.getElementById('password-rules-count');

        if (counter) {
            counter.textContent = blank
                ? total + (total === 1 ? ' rule' : ' rules') + ' to meet'
                : met === total
                    ? 'All ' + total + ' rules met - strong password'
                    : met + ' of ' + total + ' rules met';
            counter.classList.toggle('complete', met === total && !blank);
        }

        // This earns its place: browsers refill a password field on their.
        // a password nobody entered, which reads as the form being wrong.
        const length = document.getElementById('password-length-count');

        if (length) {
            const min = RULES.password.min;
            const max = RULES.password.max;
            const n = pass.length;

            length.textContent = blank
                ? ''
                : n + (n === 1 ? ' character' : ' characters') + ' (needs ' + min + ')';

            length.classList.toggle('over', n > max);
            length.classList.toggle('short', n < min);
        }
    }

    return met;
}

function passwordProblem(pass, email) {
    const r = checkPasswordRules(pass, email);

    if (!r.length)          return 'Password must be ' + RULES.password.min + '-' + RULES.password.max + ' characters.';
    if (!r.upper)          return 'Password needs at least 1 uppercase letter (A-Z).';
    if (!r.lower)          return 'Password needs at least 1 lowercase letter (a-z).';
    if (!r.number)         return 'Password needs at least 1 number (0-9).';
    if (!r.special)        return 'Password needs at least 1 special character.';
    if (!r.not_common)     return 'That password is too common. Please choose another.';
    if (!r.not_sequential) return 'Password contains 4 or more letters or digits in order.';
    if (!r.not_repeated)   return 'Password contains 3 or more of the same character in a row.';
    if (!r.not_email)      return 'Password must not be based on your email address.';

    return '';
}


function normalisePhone(phone) {
    const trimmed = phone.trim();

    if (trimmed.charAt(0) === '+') {
        return '+' + trimmed.slice(1).replace(/\D/g, '');
    }

    return trimmed.replace(/\D/g, '');
}

function phoneProblem(raw, country) {
    const maxChars = RULES.phone.maxChars || MAX.contact;

    let text = raw;

    if (text.charAt(0) === '(') {
        const close = text.indexOf(')');

        if (close === -1 || close === 1) {
            return 'Contact number has an unfinished bracket.';
        }

        text = text.slice(1, close) + text.slice(close + 1);
    }

    if (!/^\+?[0-9 ./+-]+$/.test(text)) {
        return 'Contact number may only contain digits, spaces, +, -, brackets and dots.';
    }

    const plusPos = text.indexOf('+');

    if (plusPos !== -1 && plusPos !== 0) {
        return 'Put the + at the very start of the number, or leave it out entirely.';
    }

    if (/[.\s/-]{2,}/.test(text)) {
        return 'Contact number has a stray space or symbol.';
    }

    const hasPlus = plusPos === 0;
    const digits = (hasPlus ? text.slice(1) : text).replace(/\D/g, '');

    if (digits.length > 15) {
        return 'That is more digits than any phone number can have.';
    }

    const plan = RULES.phone.dialPlan[country];

    if (!plan) {
        if (!hasPlus || !/^[1-9][0-9]{7,14}$/.test(digits)) {
            return 'Use international format: +<country code> then the number.';
        }

        if (digits.length + 1 > maxChars) {
            return 'Contact number is too long for the ' + maxChars + ' character limit.';
        }

        return '';
    }

    let nsn;

    if (hasPlus) {
        if (digits.indexOf(plan.dial) !== 0) {
            return 'That country code does not match ' + country
                 + '. Use +' + plan.dial + ', or leave the + out for the local format.';
        }

        if (digits.length > plan.dial.length && digits.charAt(plan.dial.length) === '0') {
            return 'Remove the 0 straight after the country code.';
        }

        nsn = digits.slice(plan.dial.length);
    } else {
        nsn = digits;

        if (plan.trunk && nsn.charAt(0) === plan.trunk) {
            nsn = nsn.slice(1);
        }
    }

    if (nsn.length < plan.nsn_min || nsn.length > plan.nsn_max) {
        const expected = plan.nsn_min === plan.nsn_max
            ? plan.nsn_min + ' digits'
            : plan.nsn_min + '-' + plan.nsn_max + ' digits';

        return 'A ' + country + ' number needs ' + expected
             + ' after the country code. Yours has ' + nsn.length + '.';
    }

    if (plan.dial.length + nsn.length + 1 > maxChars) {
        return 'That number is too long to store. Please check it and try again.';
    }

    return '';
}

function updatePhoneHint(form) {
    const select = form.querySelector('#customer_country');
    const hint = document.getElementById('phone-hint');

    if (!select || !hint) {
        return;
    }

    const text = RULES.hints[select.value];

    if (text) {
        hint.textContent = text;
    }

    const input = form.querySelector('#customer_contact');

    if (input && select.value && RULES.phone.dialPlan[select.value]) {
        input.placeholder = '+' + RULES.phone.dialPlan[select.value].dial + ' 20 7946 0018';
    }
}



function validateField(input, form) {
    const value = input.value.trim();
    const name = input.name;

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
        if (/\s/.test(value)) {
            setError(input, 'Email address cannot contain spaces.');
            return false;
        }
        if (!emailRegex.test(value)) {
            setError(input, 'Please enter a valid email address, for example name@example.com.');
            return false;
        }
    }

    if (name === 'customer_contact') {
        const problem = phoneProblem(value, countryValue(form));
        setError(input, problem);
        return problem === '';
    }

    // Only the sign-up form gets the strength rules.
    // customer_pass is an EXISTING password: refusing anything that fails.
    if (name === 'customer_pass' && form.id === 'register-form') {
        const email = form.querySelector('#customer_email');
        const problem = passwordProblem(input.value, email ? email.value : '');
        updatePasswordChecklist(form, input.value, email ? email.value : '');
        setError(input, problem);
        return problem === '';
    }

    if (name === 'customer_pass_confirm') {
        const pass = form.querySelector('#customer_pass');
        const problem = pass && pass.value !== input.value
            ? 'The two passwords do not match.'
            : '';
        setError(input, problem);
        return problem === '';
    }

    if (MAX[name] !== undefined && value.length > MAX[name]) {
        setError(input, 'Must be ' + MAX[name] + ' characters or fewer.');
        return false;
    }

    setError(input, '');
    return true;
}

function countryValue(form) {
    const select = form ? form.querySelector('#customer_country') : null;
    return select ? select.value : '';
}

function initValidate() {
    const form = document.getElementById('register-form')
              || document.getElementById('login-form');

    if (!form) {
        return;
    }

    loadRules(form);
    updatePhoneHint(form);


    form.querySelectorAll('input, select').forEach(function (input) {
        input.addEventListener('blur', function () {
            validateField(input, form);
        });
    });

    // so one listener covers every way a password can arrive.
    const passField = form.querySelector('#customer_pass');
    const emailField = form.querySelector('#customer_email');
    const countryField = form.querySelector('#customer_country');

    if (passField && form.id === 'register-form') {
        const repaint = function () {
            updatePasswordChecklist(form, passField.value, emailField ? emailField.value : '');
        };

        passField.addEventListener('input', repaint);

        if (emailField) {
            emailField.addEventListener('input', repaint);
        }

        repaint();
    }

    if (countryField) {
        countryField.addEventListener('change', function () {
            updatePhoneHint(form);

            const contact = form.querySelector('#customer_contact');

            if (contact && contact.value.trim() !== '') {
                validateField(contact, form);
            }
        });
    }

    form.addEventListener('submit', function (event) {
        

        clearErrors(form);

        let valid = true;
        let firstBad = null;

        form.querySelectorAll('input, select').forEach(function (input) {
            if (!validateField(input, form)) {
                valid = false;
                if (firstBad === null) {
                    firstBad = input;
                }
            }
        });

        if (!valid) {

            event.preventDefault();
            if (firstBad) {
                firstBad.focus();
            }
            return;
        }


        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Please wait...';
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initValidate);
} else {
    initValidate();
}
