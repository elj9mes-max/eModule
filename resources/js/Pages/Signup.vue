<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';

const props = defineProps({
    initialRole: {
        type: String,
        default: 'student',
    },
});

const role = ref(props.initialRole || 'student'); // 'student' | 'teacher'

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: role.value,
});

const status = ref('idle'); // 'idle' | 'submitting' | 'error'
const errorMessage = ref('');

const setRole = (newRole) => {
    role.value = newRole;
    form.value.role = newRole;
    errorMessage.value = '';
};

const handleSubmit = () => {
    if (!form.value.name.trim()) {
        errorMessage.value = 'Please enter your full name.';
        status.value = 'error';
        return;
    }
    if (!form.value.email.trim()) {
        errorMessage.value = 'Please enter your email address.';
        status.value = 'error';
        return;
    }
    if (!form.value.password) {
        errorMessage.value = 'Please enter a password.';
        status.value = 'error';
        return;
    }
    if (form.value.password.length < 8) {
        errorMessage.value = 'Password must be at least 8 characters.';
        status.value = 'error';
        return;
    }
    if (form.value.password !== form.value.password_confirmation) {
        errorMessage.value = 'Password confirmation does not match.';
        status.value = 'error';
        return;
    }

    status.value = 'submitting';
    errorMessage.value = '';

    router.post('/register', form.value, {
        onError: (errors) => {
            status.value = 'error';
            errorMessage.value = Object.values(errors)[0] || 'Registration failed. Please check your inputs.';
        },
        onFinish: () => {
            if (status.value === 'submitting') {
                status.value = 'idle';
            }
        },
    });
};
</script>

<template>
    <Head title="Create an Account • WEBREAD" />

    <div class="webread-page">
        <!-- Google Fonts: Fredoka & Nunito -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700;800;900&family=Nunito:wght@600;700;800;900&display=swap');
        </component>

        <main class="signup-core">
            <!-- Header -->
            <header class="webread-header">
                <h1 class="brand-title">
                    <span class="brand-web">WEB</span><span class="brand-read">READ</span>
                </h1>
                <p class="brand-tagline">Join our playful learning community</p>
            </header>

            <!-- Card -->
            <div class="hologram-card">
                <!-- Role Selector -->
                <div class="role-selector-bar">
                    <button
                        type="button"
                        class="role-btn"
                        :class="{ active: role === 'student' }"
                        @click="setRole('student')"
                    >
                        🎒 Student Account
                    </button>
                    <button
                        type="button"
                        class="role-btn"
                        :class="{ active: role === 'teacher' }"
                        @click="setRole('teacher')"
                    >
                        🍎 Teacher Account
                    </button>
                </div>

                <div class="signup-title-wrap">
                    <h2 class="card-title">
                        {{ role === 'student' ? 'Create Student Account' : 'Create Teacher Account' }}
                    </h2>
                    <p class="card-desc">
                        {{ role === 'student' ? 'Unlock exciting books, complete quizzes, and collect star badges!' : 'Publish learning modules, review assignments, and guide students.' }}
                    </p>
                </div>

                <!-- Error Banner -->
                <div v-if="errorMessage" class="error-banner" role="alert">
                    <span class="error-icon">&#9888;&#65039;</span>
                    <span class="error-text">{{ errorMessage }}</span>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="signup-form" novalidate>
                    <div class="input-pill-wrapper">
                        <input
                            id="signup-name"
                            v-model="form.name"
                            type="text"
                            class="input-pill"
                            placeholder="FULL NAME"
                            autocomplete="name"
                            required
                        />
                    </div>

                    <div class="input-pill-wrapper">
                        <input
                            id="signup-email"
                            v-model="form.email"
                            type="email"
                            class="input-pill"
                            placeholder="EMAIL ADDRESS"
                            autocomplete="email"
                            required
                        />
                    </div>

                    <div class="input-pill-wrapper">
                        <input
                            id="signup-password"
                            v-model="form.password"
                            type="password"
                            class="input-pill"
                            placeholder="PASSWORD (MIN 8 CHARACTERS)"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <div class="input-pill-wrapper">
                        <input
                            id="signup-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="input-pill"
                            placeholder="CONFIRM PASSWORD"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <div class="btn-container">
                        <button
                            type="submit"
                            class="btn-signup-submit"
                            :class="{ 'teacher-accent': role === 'teacher' }"
                            :disabled="status === 'submitting'"
                        >
                            <span v-if="status === 'submitting'" class="btn-loading-dots">
                                <span class="dot"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </span>
                            <span v-else class="btn-text">
                                {{ role === 'student' ? 'REGISTER AS STUDENT' : 'REGISTER AS TEACHER' }}
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Footer back to login -->
                <div class="card-footer-login">
                    <span>Already have an account?</span>
                    <Link href="/" class="login-link">Log In</Link>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.webread-page {
    --brand-blue: #2395EB;
    --brand-blue-shadow: #1877C4;
    --brand-coral: #FF6B4A;
    --brand-coral-shadow: #D9492B;
    --greeting-navy: #1D2A68;
    --font-rounded: 'Fredoka', -apple-system, sans-serif;
    --font-ui: 'Nunito', -apple-system, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);

    min-height: 100vh;
    width: 100%;
    background-color: #FFFDF7;
    background-image: 
        radial-gradient(#E8E5DC 1.4px, transparent 1.4px),
        radial-gradient(#E8E5DC 1.4px, #FFFDF7 1.4px);
    background-size: 32px 32px;
    background-position: 0 0, 16px 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.25rem;
    box-sizing: border-box;
    font-family: var(--font-rounded);
}

.signup-core {
    width: 100%;
    max-width: 460px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.webread-header {
    text-align: center;
    margin-bottom: 1.25rem;
}

.brand-title {
    font-size: 2.85rem;
    font-weight: 900;
    margin: 0;
    line-height: 1;
    letter-spacing: -0.02em;
}

.brand-web { color: var(--brand-blue); }
.brand-read { color: var(--brand-coral); }

.brand-tagline {
    margin: 0.4rem 0 0 0;
    color: #727B94;
    font-size: 0.95rem;
    font-weight: 700;
}

.hologram-card {
    width: 100%;
    background: #FFFFFF;
    border-radius: 28px;
    padding: 2.25rem 2rem;
    box-shadow: 0 16px 45px rgba(29, 42, 104, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.9);
    border: 2px solid #EFECE4;
    box-sizing: border-box;
}

.role-selector-bar {
    display: flex;
    background: #F1F3F9;
    border-radius: 9999px;
    padding: 0.3rem;
    gap: 0.3rem;
    margin-bottom: 1.5rem;
}

.role-btn {
    flex: 1;
    background: transparent;
    border: none;
    font-family: var(--font-rounded);
    font-size: 0.85rem;
    font-weight: 800;
    color: #7A849E;
    padding: 0.55rem 0.75rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.role-btn.active {
    background: #FFFFFF;
    color: #1D2A68;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.signup-title-wrap {
    text-align: center;
    margin-bottom: 1.25rem;
}

.card-title {
    font-size: 1.55rem;
    font-weight: 900;
    color: #1D2A68;
    margin: 0 0 0.35rem 0;
}

.card-desc {
    font-size: 0.85rem;
    color: #727B94;
    font-weight: 700;
    margin: 0;
    line-height: 1.4;
}

.error-banner {
    background: #FFF1F0;
    border: 1.5px solid #FFCCC7;
    color: #CF1322;
    padding: 0.65rem 0.85rem;
    border-radius: 12px;
    margin-bottom: 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.84rem;
    font-weight: 700;
}

.signup-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.input-pill-wrapper {
    width: 100%;
}

.input-pill {
    width: 100%;
    height: 46px;
    border-radius: 9999px;
    border: 2px solid #E2E4EC;
    background: #FAFAFD;
    padding: 0 1.25rem;
    font-family: var(--font-rounded);
    font-size: 0.9rem;
    font-weight: 700;
    color: #242E5E;
    box-sizing: border-box;
    outline: none;
    transition: all 0.2s ease;
}

.input-pill:focus {
    border-color: var(--brand-blue);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(35, 149, 235, 0.15);
}

.btn-container {
    margin-top: 0.5rem;
}

.btn-signup-submit {
    width: 100%;
    height: 48px;
    background: linear-gradient(135deg, #2395EB 0%, #1579C4 100%);
    border: 2px solid rgba(255, 255, 255, 0.9);
    border-radius: 9999px;
    color: #FFFFFF;
    font-family: var(--font-rounded);
    font-size: 0.98rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    cursor: pointer;
    box-shadow: 0 5px 0 #0E5E99, 0 8px 18px rgba(35, 149, 235, 0.35);
    transition: transform 0.15s var(--spring-bounce), box-shadow 0.15s var(--spring-bounce);
}

.btn-signup-submit.teacher-accent {
    background: linear-gradient(135deg, #FF6B4A 0%, #E84D2A 100%);
    box-shadow: 0 5px 0 #BD3415, 0 8px 18px rgba(255, 107, 74, 0.35);
}

.btn-signup-submit:hover:not(:disabled) {
    transform: translateY(-2px);
}

.btn-signup-submit:active:not(:disabled) {
    transform: translateY(2px);
}

.card-footer-login {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.4rem;
    margin-top: 1.25rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: #727B94;
}

.login-link {
    color: var(--brand-blue);
    font-weight: 800;
    text-decoration: underline;
    text-underline-offset: 3px;
}
</style>
