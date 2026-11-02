@props(['status'])

@if ($status)
    <span class="badge-custom {{ $status->badgeClass() }}">{{ $status->label() }}</span>
@endif
