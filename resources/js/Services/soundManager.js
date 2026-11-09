import { reactive, ref, watch } from 'vue';

/**
 * Universal Sound Manager & Procedural Synthesizer for WEBREAD
 *
 * Provides instant zero-latency playful sound effects (SFX) and a cheerful,
 * uplifting procedural background melody (BGM) inspired by Nintendo / Animal Crossing,
 * with zero external file dependencies and full accessibility / volume controls.
 */
class SoundService {
    constructor() {
        this.audioCtx = null;
        this.masterGain = null;
        this.sfxGain = null;
        this.bgmGain = null;
        this.bgmFilter = null;

        // Reactive state
        this.state = reactive({
            isSupported: typeof window !== 'undefined' && ('AudioContext' in window || 'webkitAudioContext' in window),
            isInitialized: false,
            isMuted: false,
            isBgmPlaying: false,
            bgmVolume: 0.22, // Gentle background level by default
            sfxVolume: 0.45, // Crisp, friendly sound effect level
            userGestureGiven: false,
        });

        // BGM Sequencer state
        this.bgmTimer = null;
        this.currentStep = 0;
        this.tempo = 104; // Playful walking tempo (BPM)
        this.nextNoteTime = 0;
        this.scheduleAheadTime = 0.15; // Schedule 150ms ahead
        this.activeBgmNodes = new Set();

        // Rate limiting for global delegation
        this.lastClickSoundTime = 0;
        this.lastHoverSoundTime = 0;

        this.loadSettings();
        this.initAutoUnlock();
        this.initGlobalButtonSounds();
    }

    /**
     * Load persisted sound preferences from localStorage.
     */
    loadSettings() {
        if (typeof window === 'undefined') return;

        try {
            const savedMuted = localStorage.getItem('emodule_sound_muted');
            if (savedMuted !== null) {
                this.state.isMuted = savedMuted === 'true';
            }

            const savedBgmVol = localStorage.getItem('emodule_bgm_volume');
            if (savedBgmVol !== null) {
                const vol = parseFloat(savedBgmVol);
                if (!isNaN(vol)) this.state.bgmVolume = Math.max(0, Math.min(1, vol));
            }

            const savedSfxVol = localStorage.getItem('emodule_sfx_volume');
            if (savedSfxVol !== null) {
                const vol = parseFloat(savedSfxVol);
                if (!isNaN(vol)) this.state.sfxVolume = Math.max(0, Math.min(1, vol));
            }

            const savedBgmPlay = localStorage.getItem('emodule_bgm_autoplay');
            if (savedBgmPlay === 'true') {
                this.state.isBgmPlaying = true;
            }
        } catch {
            // Ignore localStorage errors
        }
    }

    /**
     * Persist current preferences to localStorage.
     */
    saveSettings() {
        if (typeof window === 'undefined') return;

        try {
            localStorage.setItem('emodule_sound_muted', String(this.state.isMuted));
            localStorage.setItem('emodule_bgm_volume', String(this.state.bgmVolume));
            localStorage.setItem('emodule_sfx_volume', String(this.state.sfxVolume));
            localStorage.setItem('emodule_bgm_autoplay', String(this.state.isBgmPlaying));
        } catch {
            // Ignore localStorage errors
        }
    }

    /**
     * Setup user interaction listener to unlock AudioContext on first tap/click.
     */
    initAutoUnlock() {
        if (typeof window === 'undefined') return;

        const unlockHandler = () => {
            this.state.userGestureGiven = true;
            this.initAudioContext();

            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            // If user intended BGM to play, start it now that audio context is active
            if (this.state.isBgmPlaying && !this.bgmTimer) {
                this.startBgmLoop();
            }

            window.removeEventListener('pointerdown', unlockHandler);
            window.removeEventListener('keydown', unlockHandler);
            window.removeEventListener('touchstart', unlockHandler);
        };

        window.addEventListener('pointerdown', unlockHandler, { passive: true, once: true });
        window.addEventListener('keydown', unlockHandler, { passive: true, once: true });
        window.addEventListener('touchstart', unlockHandler, { passive: true, once: true });
    }

    /**
     * Check if the current page context is a student view.
     */
    isStudentContext() {
        if (typeof window === 'undefined') return false;
        const path = window.location.pathname || '';
        if (path.includes('/students')) return true;
        return !!document.querySelector(
            '.clay-student-shell, .welcome-page, .clay-standalone-reader-shell, .clay-student-quiz-shell, [data-student-page]'
        );
    }

    /**
     * Setup global delegated button and interaction sound effects for student pages.
     * Every button, tab, card, and interactive control naturally gives delightful audio feedback!
     */
    initGlobalButtonSounds() {
        if (typeof window === 'undefined') return;

        // Global Click / Tap Sound Delegation
        window.addEventListener(
            'click',
            (event) => {
                if (!this.isStudentContext() || this.state.isMuted) return;

                const target = event.target.closest(
                    'button, [role="button"], a, input[type="radio"], input[type="checkbox"], summary, .clay-dock-btn, .clay-currency-pill, .clay-avatar-pod, .toy-badge, .float-quest-card, .float-mini-badge, .floating-speech-chip, [data-sound]'
                );

                if (!target) return;

                // Ignore disabled elements or explicit opt-out
                if (
                    target.disabled ||
                    target.getAttribute('aria-disabled') === 'true' ||
                    target.hasAttribute('data-no-sound') ||
                    target.closest('[data-no-sound]')
                ) {
                    return;
                }

                const now = performance.now();
                if (now - this.lastClickSoundTime < 35) {
                    return; // Prevent duplicate triggers if event bubbles
                }
                this.lastClickSoundTime = now;

                // 1. Explicit sound via data-sound attribute
                const customSound = target.getAttribute('data-sound');
                if (customSound) {
                    switch (customSound) {
                        case 'boing': this.playBoing(); return;
                        case 'sparkle': this.playSparkle(); return;
                        case 'whoosh': this.playWhoosh(); return;
                        case 'bonus': this.playBonus(); return;
                        case 'success': this.playSuccess(); return;
                        case 'pop': this.playPop(1.15); return;
                        case 'click': this.playClick(); return;
                        case 'none': return;
                    }
                }

                // 2. Semantic matching from classes or text
                const text = (target.textContent || '').toLowerCase();
                const cls = (target.className || '').toString().toLowerCase();

                if (cls.includes('btn-explore') || cls.includes('launch') || text.includes('explore') || text.includes('launch')) {
                    this.playWhoosh();
                } else if (cls.includes('avatar') || cls.includes('companion') || cls.includes('hero') || text.includes('companion')) {
                    this.playBoing();
                } else if (cls.includes('points') || cls.includes('reward') || cls.includes('bonus') || cls.includes('xp') || text.includes('bonus')) {
                    this.playBonus();
                } else if (cls.includes('stars') || cls.includes('quest') || cls.includes('streak') || text.includes('star')) {
                    this.playSparkle();
                } else if (cls.includes('finish') || cls.includes('submit') || text.includes('finish quiz') || text.includes('complete')) {
                    this.playSuccess();
                } else if (cls.includes('toggle') || cls.includes('switch') || cls.includes('pill') || cls.includes('chip') || cls.includes('filter') || cls.includes('tab') || target.type === 'radio' || target.type === 'checkbox') {
                    this.playPop(1.15);
                } else {
                    // Standard tactile bubble / wooden click
                    this.playClick();
                }
            },
            { capture: true, passive: true }
        );

        // Global Fine-Pointer Hover Sound Delegation
        window.addEventListener(
            'pointerover',
            (event) => {
                if (!this.isStudentContext() || this.state.isMuted) return;

                // Only play hover sounds for fine pointers (mouse / trackpad)
                if (
                    typeof window.matchMedia === 'function' &&
                    !window.matchMedia('(hover: hover) and (pointer: fine)').matches
                ) {
                    return;
                }

                const target = event.target.closest(
                    'button, [role="button"], a.btn, a.clay-dock-btn, a.clay-hud-back-btn, .clay-currency-pill, .toy-badge, .float-quest-card, .float-mini-badge, .floating-speech-chip, .clay-biome-chip, .module-card, [data-sound]'
                );

                if (!target) return;
                if (
                    target.disabled ||
                    target.getAttribute('aria-disabled') === 'true' ||
                    target.hasAttribute('data-no-sound') ||
                    target.closest('[data-no-sound]')
                ) {
                    return;
                }

                const now = performance.now();
                if (now - this.lastHoverSoundTime < 110) {
                    return; // Throttle to prevent audio pile-up on rapid cursor swipes
                }
                this.lastHoverSoundTime = now;

                this.playPop(1.28);
            },
            { passive: true }
        );
    }

    /**
     * Duck BGM volume temporarily (e.g. while page narration or reading aloud is active).
     */
    duckBgm(duck = true) {
        if (!this.audioCtx || !this.bgmGain || !this.state.isBgmPlaying) return;

        const now = this.audioCtx.currentTime;
        const targetGain = duck ? this.state.bgmVolume * 0.12 : this.state.bgmVolume;

        this.bgmGain.gain.cancelScheduledValues(now);
        this.bgmGain.gain.setValueAtTime(this.bgmGain.gain.value, now);
        this.bgmGain.gain.linearRampToValueAtTime(targetGain, now + 0.4);
    }

    /**
     * Initialize Web Audio context and node graph.
     */
    initAudioContext() {
        if (!this.state.isSupported || this.audioCtx) return;

        try {
            const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioCtxClass();

            // Master Gain
            this.masterGain = this.audioCtx.createGain();
            this.masterGain.gain.setValueAtTime(this.state.isMuted ? 0 : 1, this.audioCtx.currentTime);
            this.masterGain.connect(this.audioCtx.destination);

            // SFX Gain
            this.sfxGain = this.audioCtx.createGain();
            this.sfxGain.gain.setValueAtTime(this.state.sfxVolume, this.audioCtx.currentTime);
            this.sfxGain.connect(this.masterGain);

            // BGM Gain + Warm Low-Pass Filter
            this.bgmFilter = this.audioCtx.createBiquadFilter();
            this.bgmFilter.type = 'lowpass';
            this.bgmFilter.frequency.setValueAtTime(1400, this.audioCtx.currentTime); // Soft warm tone
            this.bgmFilter.Q.setValueAtTime(1.0, this.audioCtx.currentTime);

            this.bgmGain = this.audioCtx.createGain();
            this.bgmGain.gain.setValueAtTime(
                this.state.isBgmPlaying ? this.state.bgmVolume : 0,
                this.audioCtx.currentTime
            );

            this.bgmGain.connect(this.bgmFilter);
            this.bgmFilter.connect(this.masterGain);

            this.state.isInitialized = true;
        } catch (e) {
            console.warn('Web Audio API initialization failed:', e);
        }
    }

    /**
     * Ensure AudioContext is ready for playback.
     */
    ensureReady() {
        if (!this.audioCtx) {
            this.initAudioContext();
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
        return !!this.audioCtx;
    }

    /* =========================================================================
       PLAYFUL PROCEDURAL SOUND EFFECTS (SFX)
       ========================================================================= */

    /**
     * Playful bubble pop sound for hovering over badges, cards, or buttons.
     */
    playPop(pitchMultiplier = 1) {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            // Random subtle pitch variance for organic, lively feeling (±6%)
            const jitter = 0.94 + Math.random() * 0.12;
            const baseFreq = (380 + Math.random() * 40) * pitchMultiplier * jitter;

            osc.type = 'sine';
            // Bubble pitch ramp: starts mid, pops up fast, decays
            osc.frequency.setValueAtTime(baseFreq, now);
            osc.frequency.exponentialRampToValueAtTime(baseFreq * 1.85, now + 0.045);

            gain.gain.setValueAtTime(0.001, now);
            gain.gain.linearRampToValueAtTime(0.35, now + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.09);

            osc.connect(gain);
            gain.connect(this.sfxGain);

            osc.start(now);
            osc.stop(now + 0.095);
        } catch {
            // Ignore audio glitches
        }
    }

    /**
     * Bouncy cartoon spring / boing sound for switching avatars or bouncy buttons.
     */
    playBoing() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const lfo = this.audioCtx.createOscillator();
            const lfoGain = this.audioCtx.createGain();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(220, now);
            osc.frequency.exponentialRampToValueAtTime(420, now + 0.12);
            osc.frequency.exponentialRampToValueAtTime(280, now + 0.32);

            // Bouncy vibrato LFO
            lfo.frequency.setValueAtTime(18, now);
            lfoGain.gain.setValueAtTime(45, now);
            lfoGain.gain.exponentialRampToValueAtTime(2, now + 0.32);
            lfo.connect(osc.frequency);

            gain.gain.setValueAtTime(0.001, now);
            gain.gain.linearRampToValueAtTime(0.38, now + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);

            osc.connect(gain);
            gain.connect(this.sfxGain);

            lfo.start(now);
            osc.start(now);
            lfo.stop(now + 0.36);
            osc.stop(now + 0.36);
        } catch {}
    }

    /**
     * Magical star chime / twinkle sparkle for achievements, stars, and quest cards.
     */
    playSparkle() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            // Pentatonic sparkle cascade: C6, E6, G6, B6, C7, E7
            const freqs = [1046.5, 1318.5, 1567.98, 1975.5, 2093.0, 2637.0];

            freqs.forEach((freq, idx) => {
                const noteTime = now + idx * 0.042;
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, noteTime);

                gain.gain.setValueAtTime(0.001, noteTime);
                gain.gain.linearRampToValueAtTime(0.18 / (1 + idx * 0.15), noteTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, noteTime + 0.28);

                osc.connect(gain);
                gain.connect(this.sfxGain);

                osc.start(noteTime);
                osc.stop(noteTime + 0.3);
            });
        } catch {}
    }

    /**
     * Tactile snappy bubble/wood click for standard button taps and tab switches.
     */
    playClick() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'triangle';
            osc.frequency.setValueAtTime(750, now);
            osc.frequency.exponentialRampToValueAtTime(220, now + 0.035);

            gain.gain.setValueAtTime(0.001, now);
            gain.gain.linearRampToValueAtTime(0.3, now + 0.005);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);

            osc.connect(gain);
            gain.connect(this.sfxGain);

            osc.start(now);
            osc.stop(now + 0.05);
        } catch {}
    }

    /**
     * Thrilling rocket launch / adventure whoosh for the "EXPLORE" button.
     */
    playWhoosh() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const bufferSize = this.audioCtx.sampleRate * 0.55;
            const noiseBuffer = this.audioCtx.createBuffer(1, bufferSize, this.audioCtx.sampleRate);
            const output = noiseBuffer.getChannelData(0);

            for (let i = 0; i < bufferSize; i++) {
                output[i] = Math.random() * 2 - 1;
            }

            const whiteNoise = this.audioCtx.createBufferSource();
            whiteNoise.buffer = noiseBuffer;

            const filter = this.audioCtx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.Q.setValueAtTime(2.5, now);
            filter.frequency.setValueAtTime(320, now);
            filter.frequency.exponentialRampToValueAtTime(2600, now + 0.28);
            filter.frequency.exponentialRampToValueAtTime(450, now + 0.52);

            const noiseGain = this.audioCtx.createGain();
            noiseGain.gain.setValueAtTime(0.001, now);
            noiseGain.gain.linearRampToValueAtTime(0.42, now + 0.18);
            noiseGain.gain.exponentialRampToValueAtTime(0.0001, now + 0.52);

            whiteNoise.connect(filter);
            filter.connect(noiseGain);
            noiseGain.connect(this.sfxGain);

            // Add low sub-engine rumble
            const sub = this.audioCtx.createOscillator();
            const subGain = this.audioCtx.createGain();
            sub.type = 'sawtooth';
            sub.frequency.setValueAtTime(80, now);
            sub.frequency.exponentialRampToValueAtTime(240, now + 0.3);

            subGain.gain.setValueAtTime(0.001, now);
            subGain.gain.linearRampToValueAtTime(0.2, now + 0.12);
            subGain.gain.exponentialRampToValueAtTime(0.0001, now + 0.45);

            sub.connect(subGain);
            subGain.connect(this.sfxGain);

            whiteNoise.start(now);
            sub.start(now);
            whiteNoise.stop(now + 0.55);
            sub.stop(now + 0.5);
        } catch {}
    }

    /**
     * Joyful celebratory triumph fanfare for completing chapters, quizzes, or milestones.
     */
    playSuccess() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            // Cheerful ascending major 7 arpeggio: C5, E5, G5, B5, C6
            const chord = [523.25, 659.25, 783.99, 987.77, 1046.5];

            chord.forEach((freq, idx) => {
                const noteTime = now + idx * 0.075;
                const isFinal = idx === chord.length - 1;

                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = isFinal ? 'triangle' : 'sine';
                osc.frequency.setValueAtTime(freq, noteTime);

                const dur = isFinal ? 0.6 : 0.22;
                gain.gain.setValueAtTime(0.001, noteTime);
                gain.gain.linearRampToValueAtTime(0.28, noteTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, noteTime + dur);

                osc.connect(gain);
                gain.connect(this.sfxGain);

                osc.start(noteTime);
                osc.stop(noteTime + dur + 0.05);
            });
        } catch {}
    }

    /**
     * Arcade / coin reward bonus sound for XP bonus pills and treasure vaults.
     */
    playBonus() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const notes = [
                { freq: 987.77, time: now, dur: 0.07 }, // B5
                { freq: 1318.51, time: now + 0.07, dur: 0.35 }, // E6
            ];

            notes.forEach(({ freq, time, dur }) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, time);

                gain.gain.setValueAtTime(0.001, time);
                gain.gain.linearRampToValueAtTime(0.32, time + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, time + dur);

                osc.connect(gain);
                gain.connect(this.sfxGain);

                osc.start(time);
                osc.stop(time + dur + 0.02);
            });
        } catch {}
    }

    /**
     * Correct answer chime for quizzes.
     */
    playCorrect() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const notes = [
                { freq: 587.33, time: now, dur: 0.1 }, // D5
                { freq: 880.0, time: now + 0.11, dur: 0.38 }, // A5
            ];

            notes.forEach(({ freq, time, dur }) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, time);

                gain.gain.setValueAtTime(0.001, time);
                gain.gain.linearRampToValueAtTime(0.3, time + 0.015);
                gain.gain.exponentialRampToValueAtTime(0.0001, time + dur);

                osc.connect(gain);
                gain.connect(this.sfxGain);

                osc.start(time);
                osc.stop(time + dur + 0.02);
            });
        } catch {}
    }

    /**
     * Encouraging, gentle cartoon "try again" boop for incorrect quiz attempts.
     */
    playIncorrect() {
        if (this.state.isMuted || !this.ensureReady()) return;

        try {
            const now = this.audioCtx.currentTime;
            const notes = [
                { freq: 330.0, time: now, dur: 0.12 }, // E4
                { freq: 261.63, time: now + 0.13, dur: 0.25 }, // C4
            ];

            notes.forEach(({ freq, time, dur }) => {
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, time);

                gain.gain.setValueAtTime(0.001, time);
                gain.gain.linearRampToValueAtTime(0.24, time + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, time + dur);

                osc.connect(gain);
                gain.connect(this.sfxGain);

                osc.start(time);
                osc.stop(time + dur + 0.02);
            });
        } catch {}
    }

    /* =========================================================================
       PLAYFUL PROCEDURAL BACKGROUND MUSIC (BGM)
       Animal Crossing / Mario / Kirby title style warm marimba & kalimba groove
       ========================================================================= */

    /**
     * Schedule a marimba / kalimba note with gentle attack and warm decay.
     */
    scheduleMarimbaNote(freq, time, duration = 0.24, gainLevel = 0.2) {
        if (!this.audioCtx) return;

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'triangle'; // Warm woody wooden-bar timbre
            osc.frequency.setValueAtTime(freq, time);

            gain.gain.setValueAtTime(0.0001, time);
            gain.gain.linearRampToValueAtTime(gainLevel, time + 0.008);
            gain.gain.exponentialRampToValueAtTime(0.0001, time + duration);

            osc.connect(gain);
            gain.connect(this.bgmGain);

            osc.start(time);
            osc.stop(time + duration + 0.02);
            this.activeBgmNodes.add(osc);

            osc.onended = () => {
                this.activeBgmNodes.delete(osc);
            };
        } catch {}
    }

    /**
     * Schedule a gentle walking bass note.
     */
    scheduleBassNote(freq, time, duration = 0.3) {
        if (!this.audioCtx) return;

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, time);

            gain.gain.setValueAtTime(0.0001, time);
            gain.gain.linearRampToValueAtTime(0.25, time + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, time + duration);

            osc.connect(gain);
            gain.connect(this.bgmGain);

            osc.start(time);
            osc.stop(time + duration + 0.02);
            this.activeBgmNodes.add(osc);

            osc.onended = () => {
                this.activeBgmNodes.delete(osc);
            };
        } catch {}
    }

    /**
     * Schedule a soft, subtle rhythmic shaker tick.
     */
    scheduleTick(time) {
        if (!this.audioCtx) return;

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(1800, time);
            osc.frequency.exponentialRampToValueAtTime(400, time + 0.018);

            gain.gain.setValueAtTime(0.0001, time);
            gain.gain.linearRampToValueAtTime(0.04, time + 0.003);
            gain.gain.exponentialRampToValueAtTime(0.0001, time + 0.02);

            osc.connect(gain);
            gain.connect(this.bgmGain);

            osc.start(time);
            osc.stop(time + 0.025);
            this.activeBgmNodes.add(osc);

            osc.onended = () => {
                this.activeBgmNodes.delete(osc);
            };
        } catch {}
    }

    /**
     * Process 16-step BGM sequencer bar loop.
     * 4-Bar Chord Progression:
     * Bar 1: C Major 7 (C-E-G-B)
     * Bar 2: A Minor 7 (A-C-E-G)
     * Bar 3: F Major 7 (F-A-C-E)
     * Bar 4: G add9 / G7 (G-B-D-A)
     */
    stepBgmSequencer() {
        if (!this.state.isBgmPlaying || !this.audioCtx) return;

        const secondsPerBeat = 60.0 / this.tempo;
        const stepTime = secondsPerBeat / 2; // Eighth note grid (8 steps per bar * 4 bars = 32 steps)
        const totalSteps = 32;

        while (this.nextNoteTime < this.audioCtx.currentTime + this.scheduleAheadTime) {
            const step = this.currentStep;
            const time = this.nextNoteTime;
            const bar = Math.floor(step / 8);
            const beatInBar = step % 8;

            // 1. Rhythmic Shaker pulse on off-beats
            if (beatInBar % 2 === 1) {
                this.scheduleTick(time);
            }

            // 2. Playful Bouncy Bass
            if (beatInBar === 0) {
                // Downbeat root
                const rootFreqs = [130.81, 110.0, 87.31, 98.0]; // C3, A2, F2, G2
                this.scheduleBassNote(rootFreqs[bar], time, 0.35);
            } else if (beatInBar === 4) {
                // Fifth on beat 3
                const fifthFreqs = [196.0, 164.81, 130.81, 146.83]; // G3, E3, C3, D3
                this.scheduleBassNote(fifthFreqs[bar], time, 0.28);
            } else if (beatInBar === 6) {
                // Walking bounce on beat 4
                const walkFreqs = [164.81, 146.83, 110.0, 123.47]; // E3, D3, A2, B2
                this.scheduleBassNote(walkFreqs[bar], time, 0.2);
            }

            // 3. Gentle Marimba Chords & Playful Pentatonic Melody
            // C Major Pentatonic: C4 (261.6), D4 (293.7), E4 (329.6), G4 (392.0), A4 (440.0), C5 (523.3), E5 (659.3)
            switch (bar) {
                case 0: // Cmaj7 Bar
                    if (beatInBar === 1) this.scheduleMarimbaNote(329.63, time, 0.2, 0.16); // E4
                    if (beatInBar === 2) this.scheduleMarimbaNote(523.25, time, 0.24, 0.18); // C5
                    if (beatInBar === 4) this.scheduleMarimbaNote(392.0, time, 0.2, 0.16); // G4
                    if (beatInBar === 5) this.scheduleMarimbaNote(493.88, time, 0.22, 0.17); // B4
                    if (beatInBar === 7) this.scheduleMarimbaNote(659.25, time, 0.28, 0.2); // E5
                    break;
                case 1: // Am7 Bar
                    if (beatInBar === 1) this.scheduleMarimbaNote(329.63, time, 0.2, 0.16); // E4
                    if (beatInBar === 3) this.scheduleMarimbaNote(440.0, time, 0.22, 0.17); // A4
                    if (beatInBar === 4) this.scheduleMarimbaNote(523.25, time, 0.24, 0.18); // C5
                    if (beatInBar === 6) this.scheduleMarimbaNote(392.0, time, 0.2, 0.16); // G4
                    break;
                case 2: // Fmaj7 Bar
                    if (beatInBar === 1) this.scheduleMarimbaNote(349.23, time, 0.2, 0.16); // F4
                    if (beatInBar === 2) this.scheduleMarimbaNote(440.0, time, 0.22, 0.17); // A4
                    if (beatInBar === 4) this.scheduleMarimbaNote(523.25, time, 0.24, 0.18); // C5
                    if (beatInBar === 5) this.scheduleMarimbaNote(659.25, time, 0.26, 0.19); // E5
                    if (beatInBar === 7) this.scheduleMarimbaNote(587.33, time, 0.22, 0.18); // D5
                    break;
                case 3: // G7 / G add9 Bar (Playful turnaround)
                    if (beatInBar === 0) this.scheduleMarimbaNote(392.0, time, 0.2, 0.16); // G4
                    if (beatInBar === 2) this.scheduleMarimbaNote(493.88, time, 0.22, 0.17); // B4
                    if (beatInBar === 4) this.scheduleMarimbaNote(587.33, time, 0.24, 0.18); // D5
                    if (beatInBar === 6) this.scheduleMarimbaNote(783.99, time, 0.28, 0.2); // G5
                    if (beatInBar === 7) this.scheduleMarimbaNote(523.25, time, 0.18, 0.15); // C5
                    break;
            }

            this.nextNoteTime += stepTime;
            this.currentStep = (this.currentStep + 1) % totalSteps;
        }

        this.bgmTimer = window.setTimeout(() => this.stepBgmSequencer(), 60);
    }

    /**
     * Start the procedural background music loop.
     */
    startBgmLoop() {
        if (!this.ensureReady()) return;

        if (this.bgmTimer) {
            clearTimeout(this.bgmTimer);
            this.bgmTimer = null;
        }

        // Smoothly fade in BGM gain
        const now = this.audioCtx.currentTime;
        this.bgmGain.gain.cancelScheduledValues(now);
        this.bgmGain.gain.setValueAtTime(this.bgmGain.gain.value, now);
        this.bgmGain.gain.linearRampToValueAtTime(this.state.bgmVolume, now + 0.6);

        this.currentStep = 0;
        this.nextNoteTime = now + 0.05;
        this.stepBgmSequencer();
    }

    /**
     * Stop the procedural background music loop with smooth fade-out.
     */
    stopBgmLoop() {
        if (this.bgmTimer) {
            clearTimeout(this.bgmTimer);
            this.bgmTimer = null;
        }

        if (this.audioCtx && this.bgmGain) {
            const now = this.audioCtx.currentTime;
            this.bgmGain.gain.cancelScheduledValues(now);
            this.bgmGain.gain.setValueAtTime(this.bgmGain.gain.value, now);
            this.bgmGain.gain.linearRampToValueAtTime(0.0001, now + 0.4);
        }
    }

    /* =========================================================================
       USER CONTROLS & TOGGLES
       ========================================================================= */

    /**
     * Toggle BGM on or off.
     */
    toggleBgm() {
        this.ensureReady();
        this.state.isBgmPlaying = !this.state.isBgmPlaying;

        if (this.state.isBgmPlaying) {
            this.startBgmLoop();
            this.playPop(1.2);
        } else {
            this.stopBgmLoop();
            this.playPop(0.8);
        }

        this.saveSettings();
        return this.state.isBgmPlaying;
    }

    /**
     * Toggle Master Mute on or off.
     */
    toggleMute() {
        this.ensureReady();
        this.state.isMuted = !this.state.isMuted;

        if (this.audioCtx && this.masterGain) {
            const now = this.audioCtx.currentTime;
            this.masterGain.gain.cancelScheduledValues(now);
            this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, now);
            this.masterGain.gain.linearRampToValueAtTime(this.state.isMuted ? 0 : 1, now + 0.1);
        }

        if (!this.state.isMuted) {
            this.playPop(1.1);
            if (this.state.isBgmPlaying && !this.bgmTimer) {
                this.startBgmLoop();
            }
        }

        this.saveSettings();
        return this.state.isMuted;
    }

    /**
     * Set BGM volume (0.0 to 1.0).
     */
    setBgmVolume(volume) {
        const vol = Math.max(0, Math.min(1, volume));
        this.state.bgmVolume = vol;

        if (this.audioCtx && this.bgmGain && this.state.isBgmPlaying) {
            const now = this.audioCtx.currentTime;
            this.bgmGain.gain.cancelScheduledValues(now);
            this.bgmGain.gain.setValueAtTime(this.bgmGain.gain.value, now);
            this.bgmGain.gain.linearRampToValueAtTime(vol, now + 0.05);
        }

        this.saveSettings();
    }

    /**
     * Set SFX volume (0.0 to 1.0).
     */
    setSfxVolume(volume) {
        const vol = Math.max(0, Math.min(1, volume));
        this.state.sfxVolume = vol;

        if (this.audioCtx && this.sfxGain) {
            const now = this.audioCtx.currentTime;
            this.sfxGain.gain.cancelScheduledValues(now);
            this.sfxGain.gain.setValueAtTime(this.sfxGain.gain.value, now);
            this.sfxGain.gain.linearRampToValueAtTime(vol, now + 0.05);
        }

        this.saveSettings();
    }
}

// Export singleton instance
export const soundManager = new SoundService();

/**
 * Vue Composable for easy template & component integration.
 */
export function useSound() {
    return {
        state: soundManager.state,
        soundManager,
        // SFX shortcuts
        playPop: (pitch) => soundManager.playPop(pitch),
        playBoing: () => soundManager.playBoing(),
        playSparkle: () => soundManager.playSparkle(),
        playClick: () => soundManager.playClick(),
        playWhoosh: () => soundManager.playWhoosh(),
        playSuccess: () => soundManager.playSuccess(),
        playBonus: () => soundManager.playBonus(),
        playCorrect: () => soundManager.playCorrect(),
        playIncorrect: () => soundManager.playIncorrect(),
        // Control shortcuts
        toggleBgm: () => soundManager.toggleBgm(),
        toggleMute: () => soundManager.toggleMute(),
        setBgmVolume: (v) => soundManager.setBgmVolume(v),
        setSfxVolume: (v) => soundManager.setSfxVolume(v),
        startBgm: () => {
            soundManager.state.isBgmPlaying = true;
            soundManager.startBgmLoop();
            soundManager.saveSettings();
        },
        stopBgm: () => {
            soundManager.state.isBgmPlaying = false;
            soundManager.stopBgmLoop();
            soundManager.saveSettings();
        },
        duckBgm: (duck) => soundManager.duckBgm(duck),
    };
}

