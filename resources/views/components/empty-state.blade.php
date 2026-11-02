@props(['icon' => 'bi-inbox', 'title' => 'Belum ada data', 'description' => null])

<div class="empty-state" style="text-align:center; padding:var(--space-8) var(--space-4);">
    <i class="bi {{ $icon }}" style="font-size:2rem; color:var(--color-text-muted); display:block; margin-bottom:var(--space-3);"></i>
    <div style="font-weight:var(--fw-semibold); margin-bottom:4px;">{{ $title }}</div>
    @if ($description)
        <p class="text-muted text-small" style="margin:0;">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
