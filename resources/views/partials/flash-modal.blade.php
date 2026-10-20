@php
    $flashSuccess = session('success');
    $flashError = session('error') ?? session('status');
    $hasValidationErrors = $errors->any();
@endphp

@if ($flashSuccess || $flashError || $hasValidationErrors)
<div class="modal fade" id="appNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-inline-flex align-items-center gap-2" id="appNotificationModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="appNotificationModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const flashSuccess = @json($flashSuccess);
    const flashError = @json($flashError);
    const validationErrors = @json($errors->toArray());

    const toInputName = key => {
        const parts = key.split('.');
        return parts.length === 1 ? parts[0] : parts[0] + parts.slice(1).map(part => '[' + part + ']').join('');
    };

    const renderedKeys = new Set();
    document.querySelectorAll('[data-field-error]').forEach(node => {
        const key = node.dataset.fieldError;
        renderedKeys.add(key);

        const control = document.getElementsByName(toInputName(key))[0];
        if (control) control.classList.add('is-invalid');

        const owner = node.closest('.modal');
        if (owner && !bootstrap.Modal.getInstance(owner)) {
            bootstrap.Modal.getOrCreateInstance(owner).show();
        }
    });

    const unmappedMessages = [];
    let hasUnmappedValidationError = false;
    Object.entries(validationErrors).forEach(([key, messages]) => {
        if (renderedKeys.has(key)) return;
        hasUnmappedValidationError = true;
        (Array.isArray(messages) ? messages : [messages]).forEach(message => unmappedMessages.push(message));
    });
    if (flashError) unmappedMessages.push(flashError);

    if (!unmappedMessages.length && !flashSuccess) return;

    const modal = document.getElementById('appNotificationModal');
    const title = document.getElementById('appNotificationModalTitle');
    const body = document.getElementById('appNotificationModalBody');

    title.textContent = '';
    body.textContent = '';

    const icon = document.createElement('i');
    title.appendChild(icon);

    if (unmappedMessages.length) {
        icon.className = 'bi bi-exclamation-octagon-fill text-danger';
        title.appendChild(document.createTextNode(hasUnmappedValidationError ? 'Please fix the following' : 'Error'));

        if (unmappedMessages.length > 1) {
            const list = document.createElement('ul');
            list.className = 'mb-0';
            unmappedMessages.forEach(message => {
                const item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });
            body.appendChild(list);
        } else {
            body.textContent = unmappedMessages[0];
        }
    } else {
        icon.className = 'bi bi-check-circle-fill text-success';
        title.appendChild(document.createTextNode(' Success'));
        body.textContent = flashSuccess;
    }

    bootstrap.Modal.getOrCreateInstance(modal).show();
})();
</script>
@endif
