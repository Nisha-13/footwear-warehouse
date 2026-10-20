<script>
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const input = toggle.closest('.input-group') ? toggle.closest('.input-group').querySelector('input') : null;
        if (!input) return;

        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';

        const icon = toggle.querySelector('i');
        if (icon) icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';

        toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        toggle.setAttribute('title', hidden ? 'Hide password' : 'Show password');
    });
</script>
