@foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $key => $cls)
    @if (session($key))
        <div role="alert" class="mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm {{ $cls }}">
            <span>{{ session($key) }}</span>
            <button type="button" data-dismiss class="opacity-60 hover:opacity-100"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
    @endif
@endforeach