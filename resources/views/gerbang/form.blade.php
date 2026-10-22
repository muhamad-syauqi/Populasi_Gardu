@extends('layouts.app')
@section('title', $gerbang->exists ? 'Edit Gerbang' : 'Tambah Gerbang')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">{{ $gerbang->exists ? 'Edit Gerbang' : 'Tambah Gerbang' }}</h1>
    <p class="mb-4 text-sm text-slate-500">Isi informasi gerbang tol.</p>

    <form method="POST" action="{{ $gerbang->exists ? route('gerbang.update', $gerbang) : route('gerbang.store') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        @if ($gerbang->exists) @method('PUT') @endif

        <div>
            <label for="ruas_id" class="label">Ruas Tol <span class="text-red-500">*</span></label>
            <select id="ruas_id" name="ruas_id" required class="input">
                <option value="">Pilih Ruas Tol</option>
                @foreach ($ruas as $r)
                    <option value="{{ $r->id }}" @selected(old('ruas_id', $gerbang->ruas_id) == $r->id)>{{ $r->nama_ruas }}</option>
                @endforeach
                <option value="baru" @selected(old('ruas_id') === 'baru')>+ Tambah ruas tol baru...</option>
            </select>
            @error('ruas_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            <div id="ruas-baru-wrap" class="{{ old('ruas_id') === 'baru' ? '' : 'hidden' }} mt-2">
                <input id="ruas_baru" name="ruas_baru" type="text" value="{{ old('ruas_baru') }}" maxlength="255"
                       @required(old('ruas_id') === 'baru') placeholder="Nama ruas tol baru, contoh: Cipularang" class="input">
                @error('ruas_baru') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="nama_gerbang" class="label">Nama Gerbang <span class="text-red-500">*</span></label>
            <input id="nama_gerbang" name="nama_gerbang" type="text" required value="{{ old('nama_gerbang', $gerbang->nama_gerbang) }}" placeholder="Contoh: Pasteur" class="input">
            @error('nama_gerbang') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <span class="label" style="margin-bottom:0">Lokasi (Koordinat Peta)</span>
                <a id="maps-link" href="#" target="_blank" rel="noopener" class="hidden text-xs font-medium text-blue-600 hover:underline">Buka di Google Maps &nearr;</a>
            </div>

            <div class="flex gap-2">
                <input id="map-search" type="search" placeholder="Cari tempat, contoh: Gerbang Tol Pasteur" class="input" autocomplete="off">
                <button type="button" id="map-search-btn" class="btn btn-secondary shrink-0">Cari</button>
            </div>

            <div id="map" class="w-full overflow-hidden rounded-lg border border-slate-300 bg-slate-100" style="height:340px;isolation:isolate"></div>
            <p class="text-xs text-slate-500">Klik pada peta atau geser pin untuk menentukan titik gerbang. Koordinat juga bisa diketik atau ditempel dari Google Maps.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="latitude" class="label">Latitude</label>
                    <input id="latitude" name="latitude" type="text" inputmode="decimal" value="{{ old('latitude', $gerbang->latitude) }}" placeholder="-6.917464" class="input">
                    @error('latitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="longitude" class="label">Longitude</label>
                    <input id="longitude" name="longitude" type="text" inputmode="decimal" value="{{ old('longitude', $gerbang->longitude) }}" placeholder="107.619123" class="input">
                    @error('longitude') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" id="map-locate" class="btn btn-secondary">Gunakan lokasi saya</button>
                <button type="button" id="map-clear" class="btn btn-secondary">Hapus titik</button>
            </div>
        </div>

        <div>
            <label for="lokasi" class="label">Keterangan Lokasi</label>
            <input id="lokasi" name="lokasi" type="text" value="{{ old('lokasi', $gerbang->lokasi) }}" placeholder="Contoh: Bandung" class="input">
            @error('lokasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ route('gerbang.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            // ---- Ruas: tampilkan input nama ruas baru ----
            var ruasSelect = document.getElementById('ruas_id');
            var ruasWrap = document.getElementById('ruas-baru-wrap');
            var ruasInput = document.getElementById('ruas_baru');

            function syncRuas(focus) {
                var baru = ruasSelect.value === 'baru';
                ruasWrap.classList.toggle('hidden', !baru);
                ruasInput.required = baru;
                if (baru && focus) ruasInput.focus();
            }
            ruasSelect.addEventListener('change', function () { syncRuas(true); });
            syncRuas(false);

            // ---- Peta koordinat ----
            var latEl = document.getElementById('latitude');
            var lngEl = document.getElementById('longitude');
            var mapsLink = document.getElementById('maps-link');
            var searchEl = document.getElementById('map-search');

            function num(v) {
                var n = parseFloat(String(v).trim().replace(',', '.'));
                return isFinite(n) ? n : null;
            }
            function validPoint(lat, lng) {
                return lat !== null && lng !== null && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
            }

            var startLat = num(latEl.value), startLng = num(lngEl.value);
            var hasStart = validPoint(startLat, startLng);

            var map = L.map('map').setView(hasStart ? [startLat, startLng] : [-2.5, 118], hasStart ? 16 : 5);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // Scroll halaman tidak tersangkut di peta: zoom scroll aktif setelah peta diklik.
            map.scrollWheelZoom.disable();
            map.on('click focus', function () { map.scrollWheelZoom.enable(); });
            map.on('mouseout blur', function () { map.scrollWheelZoom.disable(); });

            var marker = null;

            function updateLink(lat, lng) {
                if (validPoint(lat, lng)) {
                    mapsLink.href = 'https://www.google.com/maps?q=' + lat + ',' + lng;
                    mapsLink.classList.remove('hidden');
                } else {
                    mapsLink.classList.add('hidden');
                }
            }

            function placeMarker(lat, lng) {
                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', function () {
                        var p = marker.getLatLng();
                        setPoint(p.lat, p.lng, false);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }
            }

            // Set titik dari peta/pencarian/GPS. pan = geser tampilan peta ke titik.
            function setPoint(lat, lng, pan) {
                lat = Math.round(lat * 1e6) / 1e6;
                lng = Math.round(lng * 1e6) / 1e6;
                latEl.value = lat;
                lngEl.value = lng;
                placeMarker(lat, lng);
                if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 16));
                updateLink(lat, lng);
            }

            function clearPoint() {
                latEl.value = '';
                lngEl.value = '';
                if (marker) { map.removeLayer(marker); marker = null; }
                updateLink(null, null);
            }

            // Ketik manual -> pin mengikuti
            function onTyped() {
                var lat = num(latEl.value), lng = num(lngEl.value);
                if (validPoint(lat, lng)) {
                    placeMarker(lat, lng);
                    map.setView([lat, lng], Math.max(map.getZoom(), 16));
                }
                updateLink(lat, lng);
            }
            latEl.addEventListener('change', onTyped);
            lngEl.addEventListener('change', onTyped);

            // Tempel "lat, lng" dari Google Maps ke salah satu kolom -> otomatis terpisah
            [latEl, lngEl].forEach(function (el) {
                el.addEventListener('input', function () {
                    var m = el.value.match(/^\s*(-?\d+\.\d+)\s*[,;\s]\s*(-?\d+\.\d+)\s*$/);
                    if (m && validPoint(parseFloat(m[1]), parseFloat(m[2]))) {
                        setPoint(parseFloat(m[1]), parseFloat(m[2]), true);
                    }
                });
            });

            map.on('click', function (e) { setPoint(e.latlng.lat, e.latlng.lng, false); });

            if (hasStart) { placeMarker(startLat, startLng); }
            updateLink(startLat, startLng);

            // Pencarian tempat (OpenStreetMap Nominatim)
            function search() {
                var q = searchEl.value.trim();
                if (!q) return;
                var btn = document.getElementById('map-search-btn');
                btn.disabled = true;
                fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=id&q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.length) { alert('Tempat tidak ditemukan. Coba kata kunci lain atau klik langsung pada peta.'); return; }
                        setPoint(parseFloat(res[0].lat), parseFloat(res[0].lon), true);
                    })
                    .catch(function () { alert('Pencarian gagal. Periksa koneksi internet Anda.'); })
                    .finally(function () { btn.disabled = false; });
            }
            document.getElementById('map-search-btn').addEventListener('click', search);
            searchEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); search(); }
            });

            // Lokasi saya (GPS browser; butuh HTTPS atau localhost)
            document.getElementById('map-locate').addEventListener('click', function () {
                if (!navigator.geolocation) { alert('Browser tidak mendukung geolokasi.'); return; }
                navigator.geolocation.getCurrentPosition(
                    function (pos) { setPoint(pos.coords.latitude, pos.coords.longitude, true); },
                    function () { alert('Tidak dapat mengambil lokasi. Pastikan izin lokasi diaktifkan.'); }
                );
            });

            document.getElementById('map-clear').addEventListener('click', clearPoint);
        })();
    </script>
@endpush