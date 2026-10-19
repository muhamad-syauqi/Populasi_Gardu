@props([
    'ruasId' => null,
    'gerbangId' => null,
    'garduId' => null,
    'filter' => false,
    'submit' => false,
    'withGardu' => false,
    'required' => false,
    'pickGardu' => false,   // true = dropdown nomor gardu untuk form tambah/edit
    'currentId' => null,    // id gardu yang sedang diedit
    'nomor' => null,        // nilai nomor gardu (old input / data tersimpan)
])

<div class="contents" data-cascade @if ($filter) data-cascade-filter @endif @if ($submit) data-cascade-submit @endif>
    <div>
        <label for="cascade-ruas" class="label">Ruas Tol</label>
        <select id="cascade-ruas" name="ruas_id" class="input" data-cascade-ruas
                data-selected="{{ $ruasId }}" data-placeholder="{{ $filter ? 'Semua Ruas' : 'Pilih Ruas Tol' }}">
            <option value="">{{ $filter ? 'Semua Ruas' : 'Pilih Ruas Tol' }}</option>
        </select>
    </div>

    <div>
        <label for="cascade-gerbang" class="label">
            Gerbang Tol @if ($required)<span class="text-red-500">*</span>@endif
        </label>
        <select id="cascade-gerbang" name="gerbang_id" class="input" data-cascade-gerbang @required($required)
                data-selected="{{ $gerbangId }}" data-placeholder="{{ $filter ? 'Semua Gerbang' : 'Pilih Gerbang' }}">
            <option value="">{{ $filter ? 'Semua Gerbang' : 'Pilih Gerbang' }}</option>
        </select>
        @if (! $filter)
            @error('gerbang_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @endif
    </div>

    @if ($pickGardu)
        {{-- Form tambah/edit: dropdown nomor gardu + input untuk nomor baru --}}
        <div>
            <label for="cascade-gardu" class="label">Nomor Gardu <span class="text-red-500">*</span></label>
            <select id="cascade-gardu" class="input" data-cascade-gardu required
                    data-current-id="{{ $currentId }}"
                    data-edit-url="{{ route('gardu.edit', '__ID__') }}"
                    data-placeholder="Pilih nomor gardu">
                <option value="">Pilih gerbang dulu</option>
            </select>

            <div data-cascade-new hidden class="mt-2">
                <input type="text" name="nomor_gardu" maxlength="10" value="{{ $nomor }}"
                       placeholder="Ketik nomor baru, contoh: 45" class="input" data-cascade-new-input>
            </div>

            @error('nomor_gardu') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    @elseif ($withGardu)
        {{-- Filter: dropdown nomor gardu --}}
        <div>
            <label for="cascade-gardu" class="label">Nomor Gardu</label>
            <select id="cascade-gardu" name="gardu_id" class="input" data-cascade-gardu
                    data-selected="{{ $garduId }}" data-placeholder="Semua Nomor Gardu">
                <option value="">Pilih gerbang dulu</option>
            </select>
        </div>
    @endif

    {{ $slot }}
</div>