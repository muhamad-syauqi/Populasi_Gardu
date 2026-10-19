// Dropdown berantai: Ruas Tol -> Gerbang -> Nomor Gardu.
// Data pohon dibaca dari <script type="application/json" id="cascade-data">.

const same = (a, b) => String(a) === String(b);
const NEW = '__new__';

export function gerbangOf(tree, ruasId, includeAll = false) {
    if (ruasId) return tree.find((r) => same(r.id, ruasId))?.gerbang ?? [];
    return includeAll ? tree.flatMap((r) => r.gerbang) : [];
}

export function garduOf(tree, gerbangId) {
    if (!gerbangId) return [];
    return tree.flatMap((r) => r.gerbang).find((g) => same(g.id, gerbangId))?.gardu ?? [];
}

export function ruasOfGerbang(tree, gerbangId) {
    return tree.find((r) => r.gerbang.some((g) => same(g.id, gerbangId)))?.id ?? null;
}

function fill(select, items, placeholder, selected) {
    select.replaceChildren();

    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    select.append(first);

    items.forEach((item) => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.label;
        select.append(opt);
    });

    select.value = selected && items.some((i) => same(i.id, selected)) ? String(selected) : '';
}

export function initCascades() {
    const dataEl = document.getElementById('cascade-data');
    if (!dataEl) return;

    const tree = JSON.parse(dataEl.textContent);

    document.querySelectorAll('[data-cascade]').forEach((root) => {
        const ruasEl = root.querySelector('[data-cascade-ruas]');
        const gerbangEl = root.querySelector('[data-cascade-gerbang]');
        const garduEl = root.querySelector('[data-cascade-gardu]');
        const hintEl = root.querySelector('[data-cascade-hint]');
        const newWrap = root.querySelector('[data-cascade-new]');
        const newInput = root.querySelector('[data-cascade-new-input]');
        const editUrl = garduEl?.dataset.editUrl || null;
        const formMode = Boolean(editUrl && newWrap && newInput); // dropdown nomor gardu di form tambah/edit
        const filterMode = root.hasAttribute('data-cascade-filter');
        const autoSubmit = root.hasAttribute('data-cascade-submit');

        if (!ruasEl || !gerbangEl) return;

        const renderGerbang = (selected) => {
            const items = gerbangOf(tree, ruasEl.value, filterMode).map((g) => ({ id: g.id, label: g.nama }));
            fill(gerbangEl, items, gerbangEl.dataset.placeholder, selected);
            gerbangEl.disabled = !filterMode && !ruasEl.value;
        };

        let currentList = [];

        const syncNew = (userChange = false) => {
            if (!formMode) return;

            const v = garduEl.value;
            const item = currentList.find((x) => same(x.id, v));

            if (v === NEW) {
                newWrap.hidden = false;
                newInput.required = true;
                if (userChange) {
                    newInput.value = '';
                    newInput.focus?.();
                }
            } else {
                newWrap.hidden = true;
                newInput.required = false;
                newInput.value = item ? item.nomor : '';
            }
        };

        const renderGardu = (selected) => {
            currentList = garduOf(tree, gerbangEl.value);

            if (garduEl) {
                const items = currentList.map((x) => ({ id: x.id, label: x.nomor }));
                if (formMode && gerbangEl.value) items.push({ id: NEW, label: '+ Nomor baru...' });

                // gerbang tanpa gardu di form: langsung mode input nomor baru
                if (formMode && gerbangEl.value && !currentList.length && !selected) selected = NEW;

                const placeholder = gerbangEl.value
                    ? (formMode ? 'Pilih nomor gardu' : garduEl.dataset.placeholder)
                    : 'Pilih gerbang dulu';

                fill(garduEl, items, placeholder, selected);
                garduEl.disabled = !gerbangEl.value;
            }

            syncNew(false);

            if (hintEl) {
                hintEl.textContent = !gerbangEl.value
                    ? ''
                    : currentList.length
                        ? 'Nomor yang sudah terdaftar di gerbang ini: ' + currentList.map((x) => x.nomor).join(', ')
                        : 'Belum ada gardu terdaftar di gerbang ini.';
            }
        };

        const submit = () => {
            if (autoSubmit) gerbangEl.form?.submit();
        };

        // Nilai awal (dari server)
        const gerbangSel = gerbangEl.dataset.selected || null;
        const garduSel = garduEl?.dataset.selected || null;
        const ruasSel = ruasEl.dataset.selected || (gerbangSel ? ruasOfGerbang(tree, gerbangSel) : null);

        fill(ruasEl, tree.map((r) => ({ id: r.id, label: r.nama })), ruasEl.dataset.placeholder, ruasSel);
        renderGerbang(gerbangSel);

        let garduInitial = garduSel;
        if (formMode) {
            // pulihkan dari nomor yang tersimpan / yang diketik sebelum validasi gagal
            const typed = newInput.value.trim();
            const list = garduOf(tree, gerbangEl.value);
            const match = list.find((x) => x.nomor === typed);
            garduInitial = typed ? (match ? match.id : NEW) : null;
        }
        renderGardu(garduInitial);

        ruasEl.addEventListener('change', () => {
            if (newInput) newInput.value = '';
            renderGerbang(null);
            renderGardu(null);
            submit();
        });

        gerbangEl.addEventListener('change', () => {
            if (newInput) newInput.value = '';
            renderGardu(null);
            submit();
        });

        garduEl?.addEventListener('change', () => {
            if (!formMode) return submit();

            syncNew(true);

            const v = garduEl.value;
            const currentId = garduEl.dataset.currentId || '';
            if (v && v !== NEW && !same(v, currentId)) {
                window.location.href = editUrl.replace('__ID__', v); // buka data gardu yang sudah ada
            }
        });
    });
}