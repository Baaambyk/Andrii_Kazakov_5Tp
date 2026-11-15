/* =====================================================================
   BookIt — logika interfejsu (vanilla JS, bez zależności)
   ===================================================================== */
(function () {
    'use strict';

    /* --- Motyw jasny / ciemny -------------------------------------- */
    const html = document.documentElement;

    function syncThemeIcon() {
        const dark = html.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
            }
            btn.setAttribute('aria-label', dark ? 'Włącz jasny motyw' : 'Włącz ciemny motyw');
        });
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            try {
                localStorage.setItem('bookit-theme', next);
            } catch (e) { /* ignorujemy */ }
            syncThemeIcon();
        });
    });
    syncThemeIcon();

    /* --- Walidacja formularzy (Bootstrap) ------------------------- */
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus({ preventScroll: false });
                }
            }
            form.classList.add('was-validated');
        });
    });

    /* --- Automatyczne zamykanie alertów --------------------------- */
    document.querySelectorAll('[data-autohide]').forEach(function (alertEl) {
        window.setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(alertEl).close();
            }
        }, 6500);
    });

    /* --- Modal potwierdzenia akcji destrukcyjnych ----------------- */
    let confirmDialog = null;

    function getConfirmDialog() {
        if (confirmDialog) {
            return confirmDialog;
        }

        const wrap = document.createElement('div');
        wrap.className = 'modal fade';
        wrap.tabIndex = -1;
        wrap.setAttribute('aria-hidden', 'true');
        wrap.innerHTML =
            '<div class="modal-dialog modal-dialog-centered">' +
            '  <div class="modal-content border-0 shadow">' +
            '    <div class="modal-body p-4 text-center">' +
            '      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"' +
            '           style="width:64px;height:64px;background:rgba(239,68,68,.12);color:#dc2626;font-size:1.6rem">' +
            '        <i class="bi bi-exclamation-triangle"></i>' +
            '      </div>' +
            '      <h2 class="h5 mb-2">Potwierdź operację</h2>' +
            '      <p class="text-secondary mb-4" data-confirm-text></p>' +
            '      <div class="d-flex gap-2 justify-content-center">' +
            '        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Anuluj</button>' +
            '        <button type="button" class="btn btn-danger px-4" data-confirm-ok>Tak, kontynuuj</button>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(wrap);

        const modal = new bootstrap.Modal(wrap);
        let resolver = null;
        let accepted = false;

        wrap.querySelector('[data-confirm-ok]').addEventListener('click', function () {
            accepted = true;
            modal.hide();
        });

        wrap.addEventListener('hidden.bs.modal', function () {
            if (resolver) {
                resolver(accepted);
                resolver = null;
            }
            accepted = false;
        });

        confirmDialog = {
            ask: function (message) {
                wrap.querySelector('[data-confirm-text]').textContent = message;
                modal.show();
                return new Promise(function (resolve) {
                    resolver = resolve;
                });
            }
        };

        return confirmDialog;
    }

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
            return;
        }
        if (form.dataset.confirmed === '1' || event.defaultPrevented) {
            return;
        }

        event.preventDefault();
        const accepted = await getConfirmDialog().ask(form.dataset.confirm);
        if (accepted) {
            form.dataset.confirmed = '1';
            form.requestSubmit();
        }
    });

    /* --- Ochrona przed wielokrotnym wysłaniem --------------------- */
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }
        const submitter = event.submitter;
        if (submitter && submitter.tagName === 'BUTTON') {
            window.setTimeout(function () {
                submitter.disabled = true;
                submitter.dataset.originalText = submitter.innerHTML;
                submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Przetwarzanie…';
            }, 0);
        }
    });

    /* --- Podglądanie hasła ---------------------------------------- */
    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.passwordToggle);
            if (!target) {
                return;
            }
            const show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
            btn.setAttribute('aria-label', show ? 'Ukryj hasło' : 'Pokaż hasło');
        });
    });

    /* --- Wskaźnik siły hasła -------------------------------------- */
    document.querySelectorAll('[data-password-strength]').forEach(function (input) {
        const meter = document.querySelector(input.dataset.passwordStrength);
        if (!meter) {
            return;
        }
        const bar = meter.querySelector('span');
        const label = meter.parentElement.querySelector('[data-strength-label]');

        function score(value) {
            let points = 0;
            if (value.length >= 8) points++;
            if (value.length >= 12) points++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) points++;
            if (/\d/.test(value)) points++;
            if (/[^A-Za-z0-9]/.test(value)) points++;
            return Math.min(points, 4);
        }

        input.addEventListener('input', function () {
            const value = input.value;
            const level = value === '' ? 0 : Math.max(1, score(value));
            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            const names = ['', 'Bardzo słabe', 'Słabe', 'Średnie', 'Dobre', 'Bardzo mocne'];
            bar.style.width = (level * 20) + '%';
            bar.style.background = colors[level];
            if (label) {
                label.textContent = value === '' ? '' : names[level];
                label.style.color = colors[level];
            }
        });
    });

    /* --- Zgodność dwóch haseł ------------------------------------- */
    document.querySelectorAll('[data-match]').forEach(function (input) {
        const target = document.querySelector(input.dataset.match);
        if (!target) {
            return;
        }

        function validateMatch() {
            input.setCustomValidity(input.value !== target.value ? 'Hasła nie są identyczne.' : '');
        }

        input.addEventListener('input', validateMatch);
        target.addEventListener('input', validateMatch);
    });

    /* --- Podgląd wybranego zdjęcia -------------------------------- */
    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        const preview = document.querySelector(input.dataset.imageInput);
        if (!preview) {
            return;
        }
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        });
    });

    /* --- Filtr w tabelach administracyjnych ----------------------- */
    document.querySelectorAll('[data-table-filter]').forEach(function (input) {
        const table = document.querySelector(input.dataset.tableFilter);
        if (!table) {
            return;
        }
        input.addEventListener('input', function () {
            const needle = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.hidden = needle !== '' && !row.textContent.toLowerCase().includes(needle);
            });
        });
    });

    /* --- Automatyczne wysyłanie formularzy filtrów ---------------- */
    document.querySelectorAll('[data-auto-submit]').forEach(function (control) {
        control.addEventListener('change', function () {
            const form = control.closest('form');
            if (form) {
                form.requestSubmit();
            }
        });
    });

    /* --- Daty: blokada przeszłości -------------------------------- */
    const today = new Date();
    const isoToday = today.getFullYear() + '-' +
        String(today.getMonth() + 1).padStart(2, '0') + '-' +
        String(today.getDate()).padStart(2, '0');

    document.querySelectorAll('input[type="date"][data-min-today]').forEach(function (input) {
        input.min = isoToday;
    });

    /* --- Widget rezerwacji ---------------------------------------- */
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        initBookingWidget(bookingForm);
    }

    function initBookingWidget(form) {
        const dateInput = form.querySelector('[name="reservation_date"]');
        const startInput = form.querySelector('[name="start_time"]');
        const endInput = form.querySelector('[name="end_time"]');
        const guestsInput = form.querySelector('[name="guests"]');
        const busyBox = document.querySelector('[data-busy-slots]');
        const feedback = document.querySelector('[data-booking-error]');
        const summaryDate = document.querySelector('[data-summary-date]');
        const summaryTime = document.querySelector('[data-summary-time]');
        const summaryDuration = document.querySelector('[data-summary-duration]');
        const summaryTotal = document.querySelector('[data-summary-total]');
        const price = parseFloat(form.dataset.price || '0');
        const capacity = parseInt(form.dataset.capacity || '0', 10);
        const endpoint = form.dataset.availability;

        let busy = [];

        const moneyFormatter = new Intl.NumberFormat('pl-PL', {
            style: 'currency',
            currency: 'PLN',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });

        function toMinutes(value) {
            if (!value || value.indexOf(':') === -1) {
                return null;
            }
            const parts = value.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function formatDate(value) {
            if (!value) {
                return '—';
            }
            const d = new Date(value + 'T00:00:00');
            return d.toLocaleDateString('pl-PL', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' });
        }

        function setError(message) {
            if (feedback) {
                feedback.textContent = message || '';
                feedback.hidden = !message;
            }
            if (endInput) {
                endInput.setCustomValidity(message || '');
            }
        }

        function checkRange() {
            setError('');
            if (guestsInput && capacity > 0) {
                guestsInput.max = String(capacity);
                if (parseInt(guestsInput.value, 10) > capacity) {
                    guestsInput.setCustomValidity('Ta sala mieści maksymalnie ' + capacity + ' osób.');
                } else {
                    guestsInput.setCustomValidity('');
                }
            }

            const start = toMinutes(startInput ? startInput.value : '');
            const end = toMinutes(endInput ? endInput.value : '');

            if (start === null || end === null) {
                updateSummary(0);
                return;
            }

            if (end <= start) {
                setError('Godzina zakończenia musi być późniejsza niż rozpoczęcia.');
                updateSummary(0);
                return;
            }

            const conflict = busy.find(function (slot) {
                const bStart = toMinutes(slot.start);
                const bEnd = toMinutes(slot.end);
                return start < bEnd && end > bStart;
            });

            if (conflict) {
                setError('Termin koliduje z rezerwacją ' + conflict.start + '–' + conflict.end + '. Wybierz inny przedział.');
            }

            updateSummary((end - start) / 60);
        }

        function updateSummary(hours) {
            if (summaryDate) {
                summaryDate.textContent = formatDate(dateInput ? dateInput.value : '');
            }
            if (summaryTime) {
                summaryTime.textContent = (startInput && endInput && startInput.value && endInput.value)
                    ? startInput.value + ' – ' + endInput.value
                    : '—';
            }
            if (summaryDuration) {
                summaryDuration.textContent = hours > 0
                    ? (hours === 1 ? '1 godzina' : hours.toFixed(hours % 1 === 0 ? 0 : 1).replace('.', ',') + ' godz.')
                    : '—';
            }
            if (summaryTotal) {
                summaryTotal.textContent = hours > 0 ? moneyFormatter.format(hours * price) : '—';
            }
        }

        function renderBusy() {
            if (!busyBox) {
                return;
            }
            if (busy.length === 0) {
                busyBox.innerHTML = '<span class="time-chip free"><i class="bi bi-check2"></i> Cały dzień wolny</span>';
                return;
            }
            busyBox.innerHTML = busy.map(function (slot) {
                return '<span class="time-chip"><i class="bi bi-lock"></i> ' + slot.start + '–' + slot.end + '</span>';
            }).join('');
        }

        async function loadAvailability() {
            if (!endpoint || !dateInput || !dateInput.value) {
                return;
            }
            const requestUrl = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') +
                'room=' + encodeURIComponent(form.dataset.room) +
                '&date=' + encodeURIComponent(dateInput.value) +
                (form.dataset.exclude ? '&exclude=' + encodeURIComponent(form.dataset.exclude) : '');
            let data = null;

            try {
                const response = await fetch(requestUrl, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                data = await response.json();
                busy = Array.isArray(data.busy) ? data.busy : [];
            } catch (error) {
                busy = [];
                if (busyBox) {
                    busyBox.innerHTML = '<span class="time-chip"><i class="bi bi-wifi-off"></i> Nie udało się pobrać zajętości</span>';
                }
            }
            if (data && data.ok === false) {
                setError(data.message || 'Nie można sprawdzić dostępności.');
            }
            renderBusy();
            checkRange();
        }

        // Po zmianie daty pobieramy zajętość i aktualizujemy podsumowanie.
        if (dateInput) {
            dateInput.addEventListener('change', loadAvailability);
        }

        [startInput, endInput, guestsInput].forEach(function (input) {
            if (!input) {
                return;
            }
            input.addEventListener('change', checkRange);
            input.addEventListener('input', checkRange);
        });

        if (startInput && endInput) {
            startInput.addEventListener('change', function () {
                const start = toMinutes(startInput.value);
                const end = toMinutes(endInput.value);
                if (start !== null && (end === null || end <= start)) {
                    const suggested = Math.min(start + 60, 22 * 60);
                    const hh = String(Math.floor(suggested / 60)).padStart(2, '0');
                    const mm = String(suggested % 60).padStart(2, '0');
                    const exists = Array.from(endInput.options).some(function (o) { return o.value === hh + ':' + mm; });
                    if (exists) {
                        endInput.value = hh + ':' + mm;
                    }
                }
                checkRange();
            });
        }

        renderBusy();
        updateSummary(0);
        if (dateInput && dateInput.value) {
            loadAvailability();
        }
    }

    /* --- Efekt "unoszenia" nagłówka przy scrollu ------------------ */
    const navbar = document.querySelector('.app-navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('shadow-sm', window.scrollY > 12);
        }, { passive: true });
    }
})();
