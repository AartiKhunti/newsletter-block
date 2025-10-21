document.addEventListener('submit', async (e) => {
    if (e.target.id === 'newsletter-signup') {
        e.preventDefault();
        const form = e.target;
        const msg = form.querySelector('.nb-message');
        msg.textContent = '';
        msg.removeAttribute('role');

        // Clear previous field errors
        const fieldErrors = form.querySelectorAll('.nb-field-error');
        fieldErrors.forEach(err => err.textContent = '');

        const data = Object.fromEntries(new FormData(form).entries());

        // Helper function to set field error and focus
        const setFieldError = (fieldId, message) => {
            const err = form.querySelector(`#${fieldId}-error`);
            if (err) {
                err.textContent = message;
                document.getElementById(fieldId).focus();
            }
        };

        // Client-side validation
        if (!data.firstName.trim()) {
            setFieldError('firstName', 'First name is required.');
            return;
        }

        if (!data.surname.trim()) {
            setFieldError('surname', 'Surname is required.');
            return;
        }

        if (!data.emailAddress.trim()) {
            setFieldError('emailAddress', 'Email address is required.');
            return;
        }

        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(data.emailAddress)) {
            setFieldError('emailAddress', 'Please enter a valid email address.');
            return;
        }

        // Indicate submitting
        msg.textContent = 'Submitting...';
        msg.style.color = 'inherit';
        msg.setAttribute('role', 'status');

        try {
            const res = await fetch(nbData.restUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();

            if (json.success) {
                msg.textContent = 'Thanks for subscribing! A confirmation email has been sent.';
                msg.style.color = 'green';
                msg.setAttribute('role', 'status');
                form.reset();
            } else {
                msg.textContent = json.error || 'Something went wrong.';
                msg.style.color = 'red';
                msg.setAttribute('role', 'alert');
            }
        } catch (err) {
            msg.textContent = 'Network error. Please try again.';
            msg.style.color = 'red';
            msg.setAttribute('role', 'alert');
        }
    }
});
