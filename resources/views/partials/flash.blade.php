@if (session('status'))
    <div class="alert-custom alert-success-custom" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert-custom alert-danger-custom" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul class="mb-0" style="padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
