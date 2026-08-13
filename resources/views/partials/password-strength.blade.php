{{--
    Live password strength meter (Weak / Moderate / Strong).

    Usage:
        @include('partials.password-strength', ['input' => 'new_password'])
        @include('partials.password-strength', [
            'input'     => 'new_password',
            'confirm'   => 'new_password_confirmation',
            'matchNote' => 'pwMatchNote',
        ])

    'input'     — id of the password field to watch (required)
    'confirm'   — id of a confirm-password field (optional)
    'matchNote' — id of an element to write the match status into (optional)
--}}

@once
    @push('styles')
        <style>
            .pw-meter-track {
                height: 6px;
                border-radius: 4px;
                background: #e9ecef;
                overflow: hidden;
            }
            .pw-meter-fill {
                height: 100%;
                width: 0;
                border-radius: 4px;
                transition: width 0.25s ease, background-color 0.25s ease;
            }
            .pw-strength-weak { background-color: #dc3545; }
            .pw-strength-moderate { background-color: #f0ad00; }
            .pw-strength-strong { background-color: #18b318; }
            .pw-text-weak { color: #dc3545; }
            .pw-text-moderate { color: #b47600; }
            .pw-text-strong { color: #158a15; }
            .pw-check {
                font-size: 0.72rem;
                color: #6c757d;
                display: flex;
                align-items: center;
                gap: 0.35rem;
            }
            .pw-check.met { color: #158a15; }
            .pw-check i { width: 12px; }
        </style>
    @endpush
@endonce

<div class="pw-strength-box mt-2 d-none"
     data-pw-meter="{{ $input }}"
     @isset($confirm) data-pw-confirm="{{ $confirm }}" @endisset
     @isset($matchNote) data-pw-match="{{ $matchNote }}" @endisset>
    <div class="d-flex align-items-center justify-content-between mb-1">
        <span class="small fw-semibold text-secondary">Password Strength</span>
        <span class="small fw-bold" data-pw-label>&mdash;</span>
    </div>
    <div class="pw-meter-track mb-2">
        <div class="pw-meter-fill" data-pw-fill></div>
    </div>
    <div class="row g-1">
        <div class="col-6"><div class="pw-check" data-rule="length"><i class="fa-regular fa-circle"></i> At least 8 characters</div></div>
        <div class="col-6"><div class="pw-check" data-rule="case"><i class="fa-regular fa-circle"></i> Upper &amp; lowercase</div></div>
        <div class="col-6"><div class="pw-check" data-rule="number"><i class="fa-regular fa-circle"></i> At least 1 number</div></div>
        <div class="col-6"><div class="pw-check" data-rule="symbol"><i class="fa-regular fa-circle"></i> A symbol (!&#64;#$)</div></div>
    </div>
    <p class="small text-muted mt-2 mb-0" data-pw-suggestion></p>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const rules = {
                    length: pw => pw.length >= 8,
                    case: pw => /[a-z]/.test(pw) && /[A-Z]/.test(pw),
                    number: pw => /[0-9]/.test(pw),
                    symbol: pw => /[^A-Za-z0-9]/.test(pw)
                };

                const tips = {
                    length: 'make it at least 8 characters',
                    case: 'mix uppercase and lowercase letters',
                    number: 'add a number',
                    symbol: 'add a symbol like ! @ # $'
                };

                document.querySelectorAll('[data-pw-meter]').forEach(function (box) {
                    const input = document.getElementById(box.dataset.pwMeter);
                    if (!input) return;

                    const confirmInput = box.dataset.pwConfirm ? document.getElementById(box.dataset.pwConfirm) : null;
                    const matchNote = box.dataset.pwMatch ? document.getElementById(box.dataset.pwMatch) : null;
                    const fill = box.querySelector('[data-pw-fill]');
                    const label = box.querySelector('[data-pw-label]');
                    const suggestion = box.querySelector('[data-pw-suggestion]');

                    function checkMatch() {
                        if (!confirmInput || !matchNote) return;
                        if (confirmInput.value === '' || input.value === '') {
                            matchNote.classList.add('d-none');
                            return;
                        }
                        matchNote.classList.remove('d-none');
                        const same = confirmInput.value === input.value;
                        matchNote.textContent = same ? 'Passwords match.' : 'Passwords do not match yet.';
                        matchNote.className = 'small mt-2 mb-0 fw-semibold ' + (same ? 'pw-text-strong' : 'pw-text-weak');
                    }

                    function evaluate() {
                        const pw = input.value;

                        if (pw === '') {
                            box.classList.add('d-none');
                            checkMatch();
                            return;
                        }
                        box.classList.remove('d-none');

                        let passed = 0;
                        const missing = [];
                        box.querySelectorAll('.pw-check').forEach(function (el) {
                            const ok = rules[el.dataset.rule](pw);
                            el.classList.toggle('met', ok);
                            el.querySelector('i').className = ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
                            if (ok) { passed++; } else { missing.push(tips[el.dataset.rule]); }
                        });

                        // Very short passwords stay Weak regardless of character variety
                        let level;
                        if (pw.length < 6 || passed <= 2) {
                            level = { name: 'Weak', pct: 33, cls: 'pw-strength-weak', text: 'pw-text-weak' };
                        } else if (passed === 3) {
                            level = { name: 'Moderate', pct: 66, cls: 'pw-strength-moderate', text: 'pw-text-moderate' };
                        } else {
                            level = { name: 'Strong', pct: 100, cls: 'pw-strength-strong', text: 'pw-text-strong' };
                        }

                        fill.style.width = level.pct + '%';
                        fill.className = 'pw-meter-fill ' + level.cls;
                        label.textContent = level.name;
                        label.className = 'small fw-bold ' + level.text;
                        suggestion.textContent = missing.length === 0
                            ? 'Great! This is a strong password.'
                            : 'Suggestion: ' + missing.join(', ') + '.';

                        checkMatch();
                    }

                    input.addEventListener('input', evaluate);
                    if (confirmInput) confirmInput.addEventListener('input', checkMatch);
                    if (input.value !== '') evaluate();
                });
            });
        </script>
    @endpush
@endonce
