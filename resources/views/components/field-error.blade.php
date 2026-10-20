@props(['field' => null])

@if ($field && $errors->has($field))
    <div class="invalid-feedback d-block field-error mt-1" data-field-error="{{ $field }}">
        @foreach ($errors->get($field) as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif
