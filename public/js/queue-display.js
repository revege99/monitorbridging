(() => {
    const stateNode = document.getElementById('queue-display-state');
    const script = document.currentScript;
    const initial = JSON.parse(stateNode?.textContent || '{}');
    const stateUrl = script?.dataset.stateUrl;
    const element = (id) => document.getElementById(id);
    const soundButton = element('sound');

    let lastSeenId = Number(initial.latest?.id ?? 0);
    let soundEnabled = localStorage.getItem('queue-display-sound') === 'on';
    let speechBusy = false;
    let speechGeneration = 0;
    const speechQueue = [];
    const queuedSpeechIds = new Set();

    function updateClock() {
        const now = new Date();
        element('clock').textContent = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        }).replace('.', ':');
        element('date').textContent = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    }

    function setSoundState() {
        const waiting = speechQueue.length;
        soundButton.textContent = soundEnabled
            ? (waiting ? `Suara aktif · ${waiting} menunggu` : 'Suara panggilan aktif')
            : 'Aktifkan suara panggilan';
        soundButton.classList.toggle('active', soundEnabled);
    }

    function speechCase(value) {
        return String(value || '')
            .split(/(\s+|-)/)
            .map((part) => /^\p{Lu}{2,}$/u.test(part)
                ? part.charAt(0) + part.slice(1).toLocaleLowerCase('id-ID')
                : part)
            .join('');
    }

    function speechText(call) {
        const queueNumber = String(call.nomor).replace(/[-_/]/g, ' ');
        const patient = speechCase(call.nama);
        const clinic = speechCase(call.spesialis || 'poli tujuan');
        const doctor = speechCase(
            String(call.dokter || '').replace(/^\s*(?:dr\.?|dokter)\s*/i, '').trim(),
        );

        return `Nomor antrean ${queueNumber}, atas nama ${patient}. Silakan menuju ${clinic}${doctor ? `, dengan dokter ${doctor}` : ''}.`;
    }

    function showCall(call, pulse = false) {
        if (!call) return;

        element('empty').classList.add('hidden');
        element('call').classList.remove('hidden');
        element('number').textContent = call.nomor;
        element('patient').textContent = call.nama;
        element('clinic-name').textContent = call.spesialis || '-';
        element('doctor').textContent = call.dokter || '-';

        if (pulse) {
            element('hero').classList.remove('pulse');
            void element('hero').offsetWidth;
            element('hero').classList.add('pulse');
        }
    }

    function processSpeechQueue() {
        if (!soundEnabled || speechBusy || speechQueue.length === 0) return;

        const call = speechQueue.shift();
        const id = Number(call.id);
        const generation = speechGeneration;
        let completed = false;

        speechBusy = true;
        showCall(call, true);
        setSoundState();

        const finish = () => {
            if (completed || generation !== speechGeneration) return;

            completed = true;
            speechBusy = false;
            queuedSpeechIds.delete(id);
            setSoundState();
            processSpeechQueue();
        };

        const watchdog = window.setTimeout(finish, 45000);
        const done = () => {
            window.clearTimeout(watchdog);
            finish();
        };
        const text = speechText(call);

        if (window.responsiveVoice) {
            window.responsiveVoice.speak(text, 'Indonesian Female', {
                rate: 0.86,
                pitch: 1,
                volume: 1,
                onend: done,
                onerror: done,
            });
            return;
        }

        if (!('speechSynthesis' in window)) {
            done();
            return;
        }

        const speech = new SpeechSynthesisUtterance(text);
        speech.lang = 'id-ID';
        speech.rate = 0.82;
        speech.pitch = 1;
        speech.onend = done;
        speech.onerror = done;

        const voices = window.speechSynthesis.getVoices();
        const voice = voices.find((item) => item.lang.toLowerCase() === 'id-id')
            || voices.find((item) => item.lang.toLowerCase().startsWith('id'))
            || voices.find((item) => /indonesia|andika|gadis/i.test(item.name));
        if (voice) speech.voice = voice;

        window.speechSynthesis.speak(speech);
    }

    function enqueueSpeech(call) {
        if (!soundEnabled || !call) return;

        const id = Number(call.id);
        if (queuedSpeechIds.has(id)) return;

        queuedSpeechIds.add(id);
        speechQueue.push(call);
        setSoundState();
        processSpeechQueue();
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function render(state) {
        if (state.latest && !speechBusy && speechQueue.length === 0) {
            showCall(state.latest);
        }

        const recent = state.recent || [];
        element('recent').innerHTML = recent.length
            ? recent.map((item) => `
                <div class="item">
                    <div class="item-number">${escapeHtml(item.nomor)}</div>
                    <div>
                        <div class="item-name">${escapeHtml(item.nama)}</div>
                        <div class="item-poli">${escapeHtml(item.spesialis || '-')}</div>
                    </div>
                    <div class="item-time">${escapeHtml(item.waktu || '')}</div>
                </div>
            `).join('')
            : '<div class="empty">Belum ada riwayat hari ini.</div>';

        const polis = state.polis || [];
        element('polis').innerHTML = polis.length
            ? polis.map((poli, index) => `
                <div class="poli-card">
                    <div class="poli-icon">${String(index + 1).padStart(2, '0')}</div>
                    <div>
                        <div class="poli-name">${escapeHtml(poli.nama)}</div>
                        <div class="poli-number">${escapeHtml(poli.nomor || '---')}</div>
                    </div>
                    <div class="poli-time">${escapeHtml(poli.waktu || 'Menunggu')}</div>
                </div>
            `).join('')
            : '<div class="empty">Tidak ada jadwal poli hari ini.</div>';
    }

    async function refresh() {
        try {
            const response = await fetch(stateUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('Display state gagal dimuat');

            const state = await response.json();
            const calls = (state.calls?.length
                ? state.calls
                : [state.latest, ...(state.recent || [])])
                .filter(Boolean);
            const incoming = calls
                .filter((call) => Number(call.id) > lastSeenId)
                .sort((first, second) => Number(first.id) - Number(second.id));

            if (calls.length) {
                lastSeenId = Math.max(lastSeenId, ...calls.map((call) => Number(call.id)));
            }

            render(state);
            incoming.forEach(enqueueSpeech);
            element('connection').textContent = 'Display terhubung';
        } catch (error) {
            element('connection').textContent = 'Mencoba menghubungkan kembali...';
        }
    }

    soundButton.addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        localStorage.setItem('queue-display-sound', soundEnabled ? 'on' : 'off');

        if (!soundEnabled) {
            speechGeneration += 1;
            speechQueue.splice(0);
            queuedSpeechIds.clear();
            speechBusy = false;
            window.responsiveVoice?.cancel();
            window.speechSynthesis?.cancel();
        } else if (initial.latest) {
            enqueueSpeech(initial.latest);
        }

        setSoundState();
    });

    updateClock();
    window.setInterval(updateClock, 1000);
    setSoundState();
    render(initial);
    window.setInterval(refresh, 1000);
})();
