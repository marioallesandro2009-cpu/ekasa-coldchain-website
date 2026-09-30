/**
 * #contact form over fetch. The form is a normal HTML POST first (works without JS);
 * this only upgrades it: the same fields go to the same action, the JSON answer is
 * shown inline, and the server stays the judge of what is valid.
 */
export default ({ error, errors = {} }) => ({
    loading: false,
    ok: false,
    message: '',
    errors,

    async submit(event) {
        const form = event.target;

        // Basic client-side check first (required, email format, min length).
        if (!form.reportValidity()) return;

        this.$refs.flash?.remove();
        this.loading = true;
        this.ok = false;
        this.message = '';
        this.errors = {};

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                this.ok = true;
                this.message = data.message || '';
                form.reset();
            } else if (response.status === 422 && data.errors) {
                this.errors = Object.fromEntries(Object.entries(data.errors).map(([field, messages]) => [field, messages[0]]));
                this.message = data.message || error;
                this.$nextTick(() => form.querySelector('[aria-invalid="true"]')?.focus());
            } else {
                this.message = data.message || error;
            }
        } catch {
            this.message = error;
        } finally {
            this.loading = false;
            setTimeout(() => this.$refs.live?.focus(), 60);
        }
    },
});
