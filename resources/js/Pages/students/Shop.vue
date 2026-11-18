<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Components/StudentLayout.vue';
import HudHeaderSkin from '@/Components/HudHeaderSkin.vue';
import TrailThemeBackdrop from '@/Components/TrailThemeBackdrop.vue';
import { useSound } from '@/Services/soundManager';
import { spawnPixarBurst, usePixarTheme, useWorldOnDocument } from '@/Composables/usePixarTheme';

const {
    playPop,
    playSparkle,
    playSuccess,
    playClick,
    playIncorrect,
} = useSound();

const { activeWorld } = usePixarTheme();
useWorldOnDocument(activeWorld);

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    shop: {
        type: Object,
        default: () => ({ items: [], inventory: {}, equipped: {} }),
    },
});

// View mode ('grid' | 'list') matching Classrooms.vue
const viewMode = ref('grid');

// Category filter
const activeCategory = ref('all');
const searchQuery = ref('');
const onlyAffordable = ref(false);

// Inspect/Purchase Dialog modal
const inspectModalOpen = ref(false);
const inspectingItem = ref(null);
const modalDialogRef = ref(null);
const purchaseSuccessToast = ref('');
const pendingItemKey = ref(null);
let toastTimer = null;

// Server-authoritative balance, inventory and loadout (never stored in the browser)
const userGems = computed(() => Number(props.user?.gamification?.gem_balance ?? 0));
const studentLevel = computed(() => Number(props.user?.gamification?.level ?? 1));
const inventory = computed(() => props.shop?.inventory ?? {});
const equipped = computed(() => props.shop?.equipped ?? {});

onMounted(() => {
    try {
        const savedView = window.localStorage?.getItem('emodule_student_shop_view');
        if (savedView === 'list' || savedView === 'grid') {
            viewMode.value = savedView;
        }
    } catch {}
});

const setViewMode = (mode) => {
    viewMode.value = mode;
    try {
        window.localStorage?.setItem('emodule_student_shop_view', mode);
    } catch {}
};

// Categories (ids match App\Enums\ShopItemCategory)
const categories = [
    { id: 'all', label: 'All', icon: '🌟' },
    { id: 'avatar', label: 'Avatars', icon: '🦊' },
    { id: 'background', label: 'Trails', icon: '🗺️' },
    { id: 'console', label: 'Headers', icon: '🖼️' },
    { id: 'booster', label: 'Chances', icon: '⚡' },
];

const categoryIcons = Object.fromEntries(categories.map((cat) => [cat.id, cat.icon]));

const categoryPalettes = {
    avatar: { border: '#D97706', soft: '#FFFBEB', ledge: '#92400E' },
    background: { border: '#0D9488', soft: '#F0FDFA', ledge: '#115E59' },
    console: { border: '#7C3AED', soft: '#F5F3FF', ledge: '#5B21B6' },
    booster: { border: '#2563EB', soft: '#EFF6FF', ledge: '#1E40AF' },
};

// Shop catalog comes from the server (shop_items table)
const shopItems = computed(() => (props.shop?.items ?? []).map((item) => ({
    ...item,
    id: item.key,
    title: item.name,
    summary: item.description ?? '',
    perk: item.perk ?? item.description ?? '',
    isConsumable: item.category === 'booster',
    headerSkin: item.metadata?.header_skin ?? null,
    trailTheme: item.metadata?.trail_theme ?? null,
    badge: item.category === 'booster' ? `MAX ${item.max_quantity}` : null,
    icon: item.metadata?.icon ?? categoryIcons[item.category] ?? '🎁',
    palette: { ...categoryPalettes[item.category], ...(item.metadata?.palette ?? {}) },
})));

// Filtered shop items
const filteredItems = computed(() => {
    return shopItems.value.filter((item) => {
        if (activeCategory.value !== 'all' && item.category !== activeCategory.value) {
            return false;
        }

        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            const matches =
                item.title.toLowerCase().includes(q) ||
                item.summary.toLowerCase().includes(q) ||
                item.perk.toLowerCase().includes(q) ||
                item.rarity.toLowerCase().includes(q);
            if (!matches) {
                return false;
            }
        }

        if (onlyAffordable.value && item.price > userGems.value) {
            return false;
        }

        return true;
    });
});

const getConsumableCount = (itemId) => Number(inventory.value[itemId] ?? 0);

// Check ownership / equipped / lock state
const isItemOwned = (item) => getConsumableCount(item.id) > 0;

const isItemEquipped = (item) => !item.isConsumable && equipped.value[item.category] === item.id;

const isLevelLocked = (item) => studentLevel.value < item.min_level;

const isAtHoldLimit = (item) => getConsumableCount(item.id) >= item.max_quantity;

// Inspect item
const openInspect = (item) => {
    playPop(1.1);
    inspectingItem.value = item;
    inspectModalOpen.value = true;
};

const closeInspect = () => {
    playClick();
    inspectModalOpen.value = false;
};

const firstError = (errors, fallback) => errors?.shop ?? Object.values(errors ?? {})[0] ?? fallback;

// Purchase / Equip actions (validated and charged on the server)
const handleBuyOrEquip = (item, event) => {
    if (!item.isConsumable && isItemOwned(item)) {
        equipItem(item);
        return;
    }

    if (pendingItemKey.value) {
        return;
    }

    if (isLevelLocked(item)) {
        playIncorrect();
        triggerToast(`Reach level ${item.min_level} to unlock ${item.title}!`);
        return;
    }

    if (item.isConsumable && isAtHoldLimit(item)) {
        playIncorrect();
        triggerToast(`Your bag holds at most ${item.max_quantity} ${item.title}.`);
        return;
    }

    if (userGems.value < item.price) {
        playIncorrect();
        triggerToast(`Need 💎 ${item.price - userGems.value} more gems!`);
        return;
    }

    const targetEl = event?.currentTarget || modalDialogRef.value;
    pendingItemKey.value = item.id;

    router.post(`/students/shop/items/${item.id}/purchase`, {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['user', 'shop'],
        optimistic: (pageProps) => ({
            user: {
                ...pageProps.user,
                gamification: {
                    ...pageProps.user.gamification,
                    gem_balance: pageProps.user.gamification.gem_balance - item.price,
                    gems_spent: pageProps.user.gamification.gems_spent + item.price,
                },
            },
            shop: {
                ...pageProps.shop,
                inventory: {
                    ...pageProps.shop.inventory,
                    [item.id]: (pageProps.shop.inventory[item.id] ?? 0) + 1,
                },
            },
        }),
        onSuccess: () => {
            playSuccess();
            if (targetEl) {
                spawnPixarBurst(targetEl, 24, document.documentElement.dataset.pixarTheme || 'toybox');
            }

            if (item.isConsumable) {
                triggerToast(`Purchased ${item.title}! (${getConsumableCount(item.id)}x in bag)`);
                return;
            }

            triggerToast(`🎉 Unlocked ${item.title}!`);
            inspectModalOpen.value = false;
            equipItem(item, false);
        },
        onError: (errors) => {
            playIncorrect();
            triggerToast(firstError(errors, `Couldn't buy ${item.title}. Try again!`));
        },
        onFinish: () => {
            pendingItemKey.value = null;
        },
    });
};

// Big-ticket items (100+ gems) open the confirm dialog instead of buying in one tap
const CONFIRM_PRICE_THRESHOLD = 100;

const buyFromCard = (item, event) => {
    if (item.price >= CONFIRM_PRICE_THRESHOLD) {
        openInspect(item);
        return;
    }

    handleBuyOrEquip(item, event);
};

// Cosmetics the layout reads from auth.user, so equipping restyles the page instantly.
const equippedAuthPatch = (item) => {
    if (item.headerSkin) {
        return { header_skin: item.headerSkin };
    }
    if (item.trailTheme) {
        return { trail_theme: item.trailTheme };
    }
    return null;
};

const equipItem = (item, notify = true) => {
    if (item.isConsumable || isItemEquipped(item)) {
        return;
    }

    router.patch(`/students/shop/items/${item.id}/equip`, {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['shop', 'auth'],
        optimistic: (pageProps) => ({
            shop: {
                ...pageProps.shop,
                equipped: { ...pageProps.shop.equipped, [item.category]: item.id },
            },
            // Swap the live HUD header instantly when equipping a header background.
            ...(equippedAuthPatch(item) && pageProps.auth?.user
                ? { auth: { ...pageProps.auth, user: { ...pageProps.auth.user, ...equippedAuthPatch(item) } } }
                : {}),
        }),
        onSuccess: () => {
            playSparkle();
            if (notify) {
                triggerToast(`Equipped ${item.title}!`);
            }
        },
        onError: (errors) => {
            playIncorrect();
            triggerToast(firstError(errors, `Couldn't equip ${item.title}.`));
        },
    });
};

const triggerToast = (msg) => {
    purchaseSuccessToast.value = msg;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        purchaseSuccessToast.value = '';
    }, 3400);
};
</script>

<template>
    <Head title="Gem Bazaar • Student Shop • WEBREAD" />

    <StudentLayout
        :user="user"
        active-nav="shop"
        :full-width="true"
    >
        <section class="clay-shop-page" aria-labelledby="shop-main-heading">
            <!-- =========================================================================
                 1. COMPACT STAGE CONTROL DECK (Cohesive with Classrooms.vue)
                 ========================================================================= -->
            <div class="shop-control-deck">
                <!-- Top Row: Title + Gem Vault + View Switcher -->
                <div class="deck-top-row">
                    <div class="deck-title-block">
                        <span class="deck-gem-medallion" aria-hidden="true">💎</span>
                        <div class="deck-title-meta">
                            <h1 id="shop-main-heading" class="deck-title">Gem Bazaar</h1>
                            <span class="deck-item-counter">
                                {{ filteredItems.length }} {{ filteredItems.length === 1 ? 'item' : 'items' }}
                            </span>
                        </div>
                        <div class="deck-view-switch" role="group" aria-label="Layout view">
                            <button
                                type="button"
                                class="view-btn"
                                :class="{ 'is-active': viewMode === 'grid' }"
                                title="Grid View"
                                aria-label="Grid View"
                                @click="setViewMode('grid')"
                            >
                                <svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
                                    <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.5" />
                                    <rect x="9" y="1.5" width="5.5" height="5.5" rx="1.5" />
                                    <rect x="1.5" y="9" width="5.5" height="5.5" rx="1.5" />
                                    <rect x="9" y="9" width="5.5" height="5.5" rx="1.5" />
                                </svg>
                                <span>Grid</span>
                            </button>
                            <button
                                type="button"
                                class="view-btn"
                                :class="{ 'is-active': viewMode === 'list' }"
                                title="List View"
                                aria-label="List View"
                                @click="setViewMode('list')"
                            >
                                <svg viewBox="0 0 16 16" width="13" height="13" fill="currentColor" aria-hidden="true">
                                    <rect x="1.5" y="2" width="13" height="3" rx="1" />
                                    <rect x="1.5" y="6.5" width="13" height="3" rx="1" />
                                    <rect x="1.5" y="11" width="13" height="3" rx="1" />
                                </svg>
                                <span>List</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Mid Row: Smooth Category Filter Pills (Glides on mobile) -->
                <div class="deck-filters-row">

                    <!-- Compact Search & Affordable Toggle -->
                    <div class="deck-search-wrap">
                        <div class="clay-search-compact" :class="{ 'has-query': searchQuery }" role="search">
                            <span class="search-mini-lens" aria-hidden="true">🔍</span>
                            <input
                                v-model="searchQuery"
                                type="search"
                                class="search-mini-input"
                                placeholder="Find header, avatar, chance…"
                                enterkeyhint="search"
                                @keydown.esc="searchQuery = ''"
                            />
                            <span v-if="searchQuery" class="search-mini-count">{{ filteredItems.length }}</span>
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="search-mini-clear"
                                aria-label="Clear search"
                                @click="searchQuery = ''"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Affordable Toggle Pill -->
                        <button
                            type="button"
                            class="affordable-pill-btn"
                            :class="{ 'is-active': onlyAffordable }"
                            :title="onlyAffordable ? 'Showing only affordable items' : 'Filter items you can afford now'"
                            @click="onlyAffordable = !onlyAffordable"
                        >
                            <span class="afford-dot" aria-hidden="true"></span>
                            <span>Can Afford</span>
                        </button>
                    </div>
                </div>

                <!-- Subtle Toast Notification -->
                <Transition name="toast-slide">
                    <div v-if="purchaseSuccessToast" class="shop-toast-banner" role="status">
                        <span class="toast-spark" aria-hidden="true">✨</span>
                        <span class="toast-txt">{{ purchaseSuccessToast }}</span>
                    </div>
                </Transition>
            </div>

            <!-- =========================================================================
                 2. ITEMS DISPLAY (GRID OR COMPACT LIST VIEW)
                 ========================================================================= -->
            <div
                v-if="filteredItems.length > 0"
                :class="[viewMode === 'grid' ? 'shop-compact-grid' : 'shop-compact-list']"
            >
                <!-- ==================== VIEW 1: COMPACT 3D CARDS ==================== -->
                <template v-if="viewMode === 'grid'">
                    <article
                        v-for="(item, idx) in filteredItems"
                        :key="item.id"
                        class="shop-card"
                        :class="[
                            `rarity-style-${item.rarity.toLowerCase()}`,
                            {
                                'is-owned': isItemOwned(item) && !item.isConsumable,
                                'is-equipped': isItemEquipped(item),
                                'is-unaffordable': item.price > userGems && !isItemOwned(item),
                            }
                        ]"
                        :style="{
                            '--item-accent': item.palette.border,
                            '--item-ledge': item.palette.ledge,
                            '--item-soft': item.palette.soft,
                            '--i': Math.min(idx, 8),
                        }"
                    >
                        <!-- Compact Diorama Scene Header (Matches Classrooms.vue card style) -->
                        <div class="card-scene" :class="{ 'has-skin-preview': item.headerSkin || item.trailTheme }" @click="openInspect(item)">
                            <!-- Live Header Background / Trail Theme Preview -->
                            <HudHeaderSkin v-if="item.headerSkin" :skin="item.headerSkin" :scrim="false" />
                            <TrailThemeBackdrop v-else-if="item.trailTheme" :theme="item.trailTheme" sample />

                            <!-- Ambient Rolling Clay Hills -->
                            <svg v-else class="scene-hills" viewBox="0 0 200 45" preserveAspectRatio="none" aria-hidden="true">
                                <path class="hill-back" d="M0,26 C40,10 80,30 130,16 C165,6 185,20 200,14 L200,45 L0,45 Z" />
                                <path class="hill-front" d="M0,35 C45,20 90,40 140,24 C175,16 190,28 200,24 L200,45 L0,45 Z" />
                            </svg>

                            <!-- Top Badges -->
                            <span class="scene-kicker">{{ item.rarity }}</span>
                            <span v-if="item.isConsumable && getConsumableCount(item.id) > 0" class="scene-stock">
                                {{ getConsumableCount(item.id) }}/{{ item.max_quantity }} in bag
                            </span>
                            <span v-else-if="item.badge" class="scene-badge">{{ item.badge }}</span>
                            <span v-else-if="isItemEquipped(item)" class="scene-active-pill">
                                Active ✓
                            </span>

                            <!-- 3D Item Medallion / Art -->
                            <div v-if="!item.headerSkin && !item.trailTheme" class="card-medallion" aria-hidden="true">
                                <span class="medallion-emoji">{{ item.icon }}</span>
                            </div>
                        </div>

                        <!-- Card Body (Compact & readable) -->
                        <div class="shop-card-body">
                            <h2 class="card-name" :title="item.title">{{ item.title }}</h2>
                            <p class="card-meta">{{ item.perk }}</p>

                            <!-- Footer: Price & 3D Tactile Push Button -->
                            <div class="card-footer">
                                <span class="card-price-badge">
                                    <span class="price-gem">💎</span>
                                    <strong class="price-val">{{ item.price }}</strong>
                                </span>

                                <div class="card-btn-pod">
                                    <button
                                        v-if="!item.isConsumable && isItemEquipped(item)"
                                        type="button"
                                        class="shop-action-btn is-active-btn"
                                        disabled
                                    >
                                        <span class="action-btn-face">Active ✓</span>
                                    </button>

                                    <button
                                        v-else-if="!item.isConsumable && isItemOwned(item)"
                                        type="button"
                                        class="shop-action-btn is-equip-btn"
                                        @click="equipItem(item)"
                                    >
                                        <span class="action-btn-face">Equip</span>
                                    </button>

                                    <button
                                        v-else-if="isLevelLocked(item)"
                                        type="button"
                                        class="shop-action-btn is-locked-btn"
                                        :title="`Unlocks at level ${item.min_level}`"
                                        @click="openInspect(item)"
                                    >
                                        <span class="action-btn-face">🔒 Lv {{ item.min_level }}</span>
                                    </button>

                                    <button
                                        v-else-if="item.isConsumable && isAtHoldLimit(item)"
                                        type="button"
                                        class="shop-action-btn is-active-btn"
                                        disabled
                                    >
                                        <span class="action-btn-face">Bag Full</span>
                                    </button>

                                    <button
                                        v-else-if="item.price <= userGems"
                                        type="button"
                                        class="shop-action-btn is-buy-btn"
                                        :disabled="pendingItemKey !== null"
                                        @click="buyFromCard(item, $event)"
                                    >
                                        <span class="action-btn-face">{{ pendingItemKey === item.id ? '…' : 'Buy' }}</span>
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="shop-action-btn is-locked-btn"
                                        :title="`Need ${item.price - userGems} more gems`"
                                        @click="openInspect(item)"
                                    >
                                        <span class="action-btn-face">🔒 -{{ item.price - userGems }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <!-- ==================== VIEW 2: COMPACT LIST ROWS ==================== -->
                <template v-else>
                    <div
                        v-for="(item, idx) in filteredItems"
                        :key="`list-${item.id}`"
                        class="shop-list-row"
                        :class="[
                            `rarity-style-${item.rarity.toLowerCase()}`,
                            {
                                'is-owned': isItemOwned(item) && !item.isConsumable,
                                'is-equipped': isItemEquipped(item),
                            }
                        ]"
                        :style="{
                            '--item-accent': item.palette.border,
                            '--item-ledge': item.palette.ledge,
                            '--item-soft': item.palette.soft,
                            '--i': Math.min(idx, 8),
                        }"
                        @click="openInspect(item)"
                    >
                        <!-- Left Medallion -->
                        <div class="list-medallion" aria-hidden="true">
                            <span class="list-icon">{{ item.icon }}</span>
                        </div>

                        <!-- Mid Info -->
                        <div class="list-main-info">
                            <div class="list-title-row">
                                <strong class="list-title">{{ item.title }}</strong>
                                <span class="list-kicker">{{ item.rarity }}</span>
                                <span v-if="isItemEquipped(item)" class="list-active-tag">Active</span>
                                <span v-else-if="item.isConsumable && getConsumableCount(item.id) > 0" class="list-stock-tag">
                                    {{ getConsumableCount(item.id) }}/{{ item.max_quantity }} in bag
                                </span>
                            </div>
                            <span class="list-meta">{{ item.perk }}</span>
                        </div>

                        <!-- Right Actions -->
                        <div class="list-actions-right" @click.stop>
                            <span class="list-price-badge">💎 {{ item.price }}</span>

                            <button
                                v-if="!item.isConsumable && isItemEquipped(item)"
                                type="button"
                                class="list-action-btn is-active-btn"
                                disabled
                            >
                                <span class="action-btn-face">Active ✓</span>
                            </button>

                            <button
                                v-else-if="!item.isConsumable && isItemOwned(item)"
                                type="button"
                                class="list-action-btn is-equip-btn"
                                @click="equipItem(item)"
                            >
                                <span class="action-btn-face">Equip</span>
                            </button>

                            <button
                                v-else-if="isLevelLocked(item)"
                                type="button"
                                class="list-action-btn is-locked-btn"
                                @click="openInspect(item)"
                            >
                                <span class="action-btn-face">🔒 Level {{ item.min_level }}</span>
                            </button>

                            <button
                                v-else-if="item.isConsumable && isAtHoldLimit(item)"
                                type="button"
                                class="list-action-btn is-active-btn"
                                disabled
                            >
                                <span class="action-btn-face">Bag Full</span>
                            </button>

                            <button
                                v-else-if="item.price <= userGems"
                                type="button"
                                class="list-action-btn is-buy-btn"
                                :disabled="pendingItemKey !== null"
                                @click="buyFromCard(item, $event)"
                            >
                                <span class="action-btn-face">{{ pendingItemKey === item.id ? '…' : 'Buy' }}</span>
                            </button>

                            <button
                                v-else
                                type="button"
                                class="list-action-btn is-locked-btn"
                                @click="openInspect(item)"
                            >
                                <span class="action-btn-face">🔒 Need 💎 {{ item.price - userGems }}</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty Catalog State (no products stocked yet) -->
            <div v-else-if="shopItems.length === 0" class="shop-empty-state" role="status">
                <span class="empty-emoji" aria-hidden="true">🏗️</span>
                <h3 class="empty-title">The Bazaar is restocking</h3>
                <p class="empty-desc">
                    New goodies are on the way. Keep earning gems in quizzes. You have 💎 {{ userGems.toLocaleString() }} ready to spend!
                </p>
            </div>

            <!-- Empty Search State -->
            <div v-else class="shop-empty-state" role="status">
                <span class="empty-emoji" aria-hidden="true">📦</span>
                <h3 class="empty-title">No items match "{{ searchQuery }}"</h3>
                <p class="empty-desc">
                    Try checking another category or clearing your filter to view all goodies!
                </p>
                <button
                    type="button"
                    class="empty-reset-btn"
                    @click="searchQuery = ''; activeCategory = 'all'; onlyAffordable = false"
                >
                    <span class="action-btn-face">Show All Goodies</span>
                </button>
            </div>

            <!-- =========================================================================
                 3. COMPACT SQUASH-AND-STRETCH 3D INSPECT & BUY DIALOG
                 ========================================================================= -->
            <Teleport to="body">
                <Transition name="px-modal">
                    <div
                        v-if="inspectModalOpen && inspectingItem"
                        class="shop-modal-backdrop"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="inspect-dialog-title"
                        @click.self="closeInspect"
                        @keydown.esc="closeInspect"
                    >
                        <div ref="modalDialogRef" class="shop-inspect-card">
                            <!-- 3D Close Key -->
                            <button
                                type="button"
                                class="modal-close-btn"
                                aria-label="Close dialog"
                                @click="closeInspect"
                            >
                                ✕
                            </button>

                            <!-- Crest Header -->
                            <div class="modal-crest-row">
                                <div class="modal-crest-badge" aria-hidden="true">
                                    <span class="crest-emoji">{{ inspectingItem.icon }}</span>
                                </div>
                                <div class="modal-crest-meta">
                                    <span class="modal-kicker-pill">{{ inspectingItem.rarity }} {{ inspectingItem.category.toUpperCase() }}</span>
                                    <h2 id="inspect-dialog-title" class="modal-title">
                                        {{ inspectingItem.title }}
                                    </h2>
                                </div>
                            </div>

                            <p class="modal-summary">
                                {{ inspectingItem.summary }}
                            </p>

                            <!-- Trail Theme Preview (sample stretch of the expedition map) -->
                            <div v-if="inspectingItem.trailTheme" class="modal-skin-preview modal-trail-preview">
                                <TrailThemeBackdrop :theme="inspectingItem.trailTheme" sample />
                                <span class="trail-preview-label">Trail preview</span>
                            </div>

                            <!-- Header Background Preview (mini HUD with the student's name) -->
                            <div v-if="inspectingItem.headerSkin" class="modal-skin-preview">
                                <HudHeaderSkin :skin="inspectingItem.headerSkin" />
                                <div class="skin-preview-content">
                                    <span class="skin-preview-avatar" aria-hidden="true">🙂</span>
                                    <div class="skin-preview-meta">
                                        <span class="skin-preview-kicker">Header preview</span>
                                        <strong class="skin-preview-name">{{ user?.name || 'Student' }}</strong>
                                    </div>
                                    <span class="skin-preview-gems">💎 {{ userGems.toLocaleString() }}</span>
                                </div>
                            </div>

                            <!-- Perk Showcase -->
                            <div v-if="inspectingItem.perk" class="modal-perk-box">
                                <span class="perk-label">👑 IN-GAME PERK</span>
                                <p class="perk-body">{{ inspectingItem.perk }}</p>
                            </div>

                            <!-- Gem Balance Ledger -->
                            <div class="modal-ledger-box">
                                <div class="ledger-line">
                                    <span class="ledger-text">Your Gems:</span>
                                    <strong class="ledger-num">💎 {{ userGems }}</strong>
                                </div>
                                <div class="ledger-line">
                                    <span class="ledger-text">Item Cost:</span>
                                    <strong class="ledger-num text-cost">- 💎 {{ inspectingItem.price }}</strong>
                                </div>
                                <div class="ledger-divider"></div>
                                <div class="ledger-line ledger-net">
                                    <span class="ledger-text">Remaining:</span>
                                    <strong
                                        class="ledger-num"
                                        :class="{ 'text-danger': userGems < inspectingItem.price }"
                                    >
                                        💎 {{ Math.max(0, userGems - inspectingItem.price) }}
                                    </strong>
                                </div>
                            </div>

                            <!-- Modal Buttons -->
                            <div class="modal-actions-bar">
                                <button
                                    type="button"
                                    class="modal-secondary-btn"
                                    @click="closeInspect"
                                >
                                    Cancel
                                </button>

                                <button
                                    v-if="!inspectingItem.isConsumable && isItemEquipped(inspectingItem)"
                                    type="button"
                                    class="modal-primary-btn is-equipped-state"
                                    disabled
                                >
                                    Active ✓
                                </button>

                                <button
                                    v-else-if="!inspectingItem.isConsumable && isItemOwned(inspectingItem)"
                                    type="button"
                                    class="modal-primary-btn is-equip-action"
                                    @click="equipItem(inspectingItem); closeInspect()"
                                >
                                    Equip Now ✨
                                </button>

                                <button
                                    v-else-if="isLevelLocked(inspectingItem)"
                                    type="button"
                                    class="modal-primary-btn is-need-more"
                                    disabled
                                >
                                    🔒 Unlocks at Level {{ inspectingItem.min_level }}
                                </button>

                                <button
                                    v-else-if="inspectingItem.isConsumable && isAtHoldLimit(inspectingItem)"
                                    type="button"
                                    class="modal-primary-btn is-equipped-state"
                                    disabled
                                >
                                    Bag Full ({{ inspectingItem.max_quantity }}/{{ inspectingItem.max_quantity }})
                                </button>

                                <button
                                    v-else-if="userGems >= inspectingItem.price"
                                    type="button"
                                    class="modal-primary-btn is-confirm-buy"
                                    :disabled="pendingItemKey !== null"
                                    @click="handleBuyOrEquip(inspectingItem, $event)"
                                >
                                    Confirm Buy (💎 {{ inspectingItem.price }})
                                </button>

                                <button
                                    v-else
                                    type="button"
                                    class="modal-primary-btn is-need-more"
                                    @click="handleBuyOrEquip(inspectingItem, $event)"
                                >
                                    Need 💎 {{ inspectingItem.price - userGems }} More
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </section>
    </StudentLayout>
</template>

<style scoped>
/* ==========================================================================
   COMPACT & MOBILE-OPTIMIZED CLAY GEM BAZAAR DESIGN SYSTEM
   Matches Classrooms.vue & ClassroomModules.vue Pixar tactile standards.
   ========================================================================== */
.clay-shop-page {
    --font-display: 'Fredoka', -apple-system, BlinkMacSystemFont, sans-serif;
    --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
    --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
    --px-shadow-rgb: 24, 20, 59;

    font-family: var(--font-body);
    width: 100%;
    margin: 0 auto;
    padding-bottom: 5.5rem;
    color: var(--px-ink, #1E1B4B);
}

/* ==========================================================================
   1. STAGE CONTROL DECK
   ========================================================================== */
.shop-control-deck {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-bottom: 0.85rem;
}

.deck-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    flex-wrap: wrap;
    width: 100%;
}

.deck-title-block {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.deck-gem-medallion {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: linear-gradient(135deg, #FDE047 0%, #F59E0B 100%);
    border: 2px solid #FBBF24;
    display: grid;
    place-items: center;
    font-size: 1.25rem;
    box-shadow:
        0 3px 0 #D97706,
        0 6px 12px rgba(245, 158, 11, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    flex-shrink: 0;
}

.deck-title-meta {
    display: flex;
    align-items: baseline;
    gap: 0.45rem;
}

.deck-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.15rem, 3.5vw, 1.35rem);
    font-weight: 800;
    color: var(--px-ink, #1E1B4B);
    line-height: 1.1;
}

.deck-item-counter {
    padding: 0.12rem 0.5rem;
    border-radius: 9999px;
    background: var(--px-tint-info-bg, #EEF2FF);
    border: 1px solid var(--px-tint-info-line, #C7D2FE);
    color: var(--px-tint-info-ink, #4338CA);
    font-family: var(--font-display);
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}

.deck-right-pod {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Tactile 3D Gem Vault Pill */
.shop-gem-vault {
    display: inline-flex;
    align-items: center;
    gap: 0.32rem;
    padding: 0.28rem 0.65rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #FFFFFF 0%, #FEF9C3 100%);
    border: 2px solid #F59E0B;
    box-shadow:
        0 3px 0 #D97706,
        0 6px 12px rgba(245, 158, 11, 0.2),
        inset 0 1px 0 #FFFFFF;
}

.vault-gem-icon {
    font-size: 0.95rem;
    line-height: 1;
}

.vault-value {
    font-family: var(--font-display);
    font-size: 0.92rem;
    font-weight: 800;
    color: #92400E;
    line-height: 1;
}

.vault-lbl {
    font-size: 0.58rem;
    font-weight: 800;
    color: #B45309;
    letter-spacing: 0.05em;
}

/* View Mode Switcher */
.deck-view-switch {
    display: inline-flex;
    align-items: center;
    background: var(--px-surface-well, #EDE9FE);
    border: 1.5px solid var(--px-line, #DDD6FE);
    border-radius: 9999px;
    padding: 2px;
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.08);
}

.view-btn {
    border: none;
    background: transparent;
    padding: 0.22rem 0.5rem;
    border-radius: 9999px;
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink-muted, #6B7280);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.view-btn.is-active {
    background: var(--px-surface-card, #FFFFFF);
    color: var(--px-ink, #1E1B4B);
    box-shadow: 0 2px 4px rgba(var(--px-shadow-rgb), 0.15), inset 0 1px 0 var(--px-rim-top, #FFFFFF);
}

/* Mid Row: Category Tabs & Search Deck */
.deck-filters-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.shop-category-tabs {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    max-width: 100%;
    padding: 2px 1px;
}

.shop-category-tabs::-webkit-scrollbar {
    display: none;
}

.shop-cat-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.65rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-line, #E2E8F0);
    background: var(--px-surface-card, #FFFFFF);
    font-family: var(--font-display);
    font-size: 0.76rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    cursor: pointer;
    white-space: nowrap;
    transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
    box-shadow: 0 2px 0 var(--px-ledge, #CBD5E1);
}

.shop-cat-btn:hover {
    transform: translateY(-1.5px);
    color: var(--px-ink, #1E1B4B);
}

.shop-cat-btn.is-active {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%);
    border-color: #4338CA;
    color: #FFFFFF;
    box-shadow: 0 2px 0 #312E81, 0 4px 10px rgba(79, 70, 229, 0.3);
}

.cat-counter {
    font-size: 0.62rem;
    padding: 0.05rem 0.35rem;
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.08);
}

.shop-cat-btn.is-active .cat-counter {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}

.deck-search-wrap {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-grow: 1;
    justify-content: flex-end;
}

/* Compact Search Bar (matches Classrooms.vue) */
.clay-search-compact {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-line, #CBD5E1);
    border-radius: 9999px;
    padding: 0.25rem 0.55rem;
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.06);
    min-width: min(100%, 200px);
    max-width: 280px;
    flex-grow: 1;
}

.search-mini-lens {
    font-size: 0.8rem;
    line-height: 1;
    margin-right: 0.35rem;
    opacity: 0.7;
}

.search-mini-input {
    border: none;
    background: transparent;
    font-family: var(--font-body);
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--px-ink, #1E1B4B);
    outline: none;
    width: 100%;
}

.search-mini-input::placeholder {
    color: var(--px-ink-muted, #94A3B8);
}

.search-mini-count {
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    margin-right: 0.25rem;
}

.search-mini-clear {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #EF4444;
    color: #FFFFFF;
    border: none;
    font-size: 0.58rem;
    font-weight: 900;
    display: grid;
    place-items: center;
    cursor: pointer;
    flex-shrink: 0;
}

.affordable-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.6rem;
    border-radius: 9999px;
    border: 1.5px solid var(--px-line, #CBD5E1);
    background: var(--px-surface-card, #FFFFFF);
    font-family: var(--font-display);
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 2px 0 var(--px-ledge, #CBD5E1);
    transition: all 0.15s ease;
}

.afford-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #CBD5E1;
}

.affordable-pill-btn.is-active {
    background: var(--px-tint-success-bg, #ECFDF5);
    border-color: var(--px-tint-success-line, #10B981);
    color: var(--px-tint-success-ink, #065F46);
    box-shadow: 0 2px 0 var(--px-tint-success-line, #047857);
}

.affordable-pill-btn.is-active .afford-dot {
    background: #10B981;
}

/* Subtle Toast Notification */
.shop-toast-banner {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.85rem;
    border-radius: 14px;
    background: var(--px-tint-warn-bg, #FEF3C7);
    border: 1.5px solid var(--px-tint-warn-line, #F59E0B);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-tint-warn-ink, #92400E);
    animation: toast-slide 0.3s var(--spring-bounce);
}

/* ==========================================================================
   2. VIEW MODE 1: COMPACT 3D CARDS GRID (Matches Classrooms.vue card style)
   ========================================================================== */
.shop-compact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr));
    gap: 0.65rem;
}

.shop-card {
    --lift: 0px;
    position: relative;
    display: flex;
    flex-direction: column;
    border-radius: 20px;
    background: linear-gradient(180deg,
        var(--px-surface-card, #FFFFFF) 0%,
        color-mix(in srgb, var(--item-accent, #6366F1) 10%, var(--px-surface-card-bottom, #F8FAFC)) 100%);
    border: 2.5px solid var(--px-card-border, #E2E8F0);
    box-shadow:
        0 5px 0 var(--item-ledge, #CBD5E1),
        0 12px 20px -8px color-mix(in srgb, var(--item-accent, #6366F1) 35%, rgba(var(--px-shadow-rgb), 0.3)),
        inset 0 1.5px 0 var(--px-rim-top, #FFFFFF);
    color: inherit;
    text-decoration: none;
    transform: translateY(var(--lift));
    transition: transform 0.22s var(--spring-bounce), box-shadow 0.22s ease;
    animation: card-pop 0.45s var(--spring-bounce) backwards;
    animation-delay: calc(var(--i, 0) * 45ms);
    -webkit-tap-highlight-color: transparent;
}

@media (hover: hover) {
    .shop-card:hover {
        --lift: -3.5px;
    }

    .shop-card:hover .action-btn-face {
        transform: translateY(-2px);
    }

    .shop-card:hover .card-medallion {
        transform: scale(1.08) rotate(-3deg);
    }
}

.shop-card:active {
    --lift: 1px;
    transition-duration: 0.08s;
}

/* Compact Diorama Scene (matches Classrooms.vue) */
/* Header background art fills the card scene as a live preview */
.card-scene.has-skin-preview > .scene-kicker,
.card-scene.has-skin-preview > .scene-badge,
.card-scene.has-skin-preview > .scene-active-pill {
    z-index: 1;
}

.modal-trail-preview {
    height: 128px;
}

.trail-preview-label {
    position: absolute;
    left: 0.6rem;
    top: 0.5rem;
    z-index: 1;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--px-ink);
    background: color-mix(in srgb, var(--px-surface-card) 85%, transparent);
    border: 1.5px solid var(--px-card-border);
}

.modal-skin-preview {
    position: relative;
    overflow: hidden;
    height: 64px;
    margin: 0 0 0.75rem;
    border-radius: 16px;
    border: 2px solid var(--px-card-border);
    background: linear-gradient(180deg, var(--px-surface-card) 0%, var(--px-surface-card-bottom) 100%);
    box-shadow: 0 3px 0 var(--px-hud-ledge), inset 0 2px 3px var(--px-rim-top);
}

.skin-preview-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    height: 100%;
    padding: 0 0.75rem;
}

.skin-preview-avatar {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 50%;
    font-size: 1.15rem;
    background: var(--px-surface-elevated);
    border: 2px solid var(--px-card-border);
    box-shadow: 0 2px 0 var(--px-hud-ledge);
}

.skin-preview-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1 1 auto;
}

.skin-preview-kicker {
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--px-ink-muted);
}

.skin-preview-name {
    font-family: var(--font-display);
    font-size: 0.95rem;
    color: var(--px-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.skin-preview-gems {
    flex-shrink: 0;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--px-ink);
    background: var(--px-surface-elevated);
    border: 1.5px solid var(--px-card-border);
}

.card-scene {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 58px;
    margin: 5px 5px 0;
    border-radius: 15px;
    overflow: hidden;
    cursor: pointer;
    background:
        radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.75) 0%, transparent 45%),
        linear-gradient(172deg,
            color-mix(in srgb, var(--item-accent, #6366F1) 22%, var(--px-surface-elevated, #FFFFFF)) 0%,
            color-mix(in srgb, var(--item-accent, #6366F1) 50%, var(--px-surface-elevated, #FFFFFF)) 100%);
    box-shadow: inset 0 2px 4px rgba(var(--px-shadow-rgb), 0.12);
}

.scene-hills {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 38px;
    pointer-events: none;
}

.hill-back {
    fill: color-mix(in srgb, var(--item-accent, #6366F1) 35%, var(--px-surface-elevated, #FFFFFF));
    opacity: 0.7;
}

.hill-front {
    fill: color-mix(in srgb, var(--item-accent, #6366F1) 50%, var(--px-surface-elevated, #FFFFFF));
    opacity: 0.9;
}

.scene-kicker {
    position: absolute;
    top: 5px;
    left: 6px;
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 900;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--px-ink, #1E1B4B);
    background: color-mix(in srgb, var(--px-surface-card, #FFFFFF) 88%, transparent);
    backdrop-filter: blur(4px);
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.scene-badge,
.scene-stock,
.scene-active-pill {
    position: absolute;
    top: 5px;
    right: 6px;
    font-family: var(--font-display);
    font-size: 0.56rem;
    font-weight: 900;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    z-index: 2;
}

.scene-badge {
    background: #F59E0B;
    color: #FFFFFF;
}

.scene-stock {
    background: #0284C7;
    color: #FFFFFF;
}

.scene-active-pill {
    background: #10B981;
    color: #FFFFFF;
}

/* 3D Medallion */
.card-medallion {
    position: relative;
    z-index: 3;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    transition: transform 0.2s var(--spring-bounce);
}

.mini-art-svg {
    width: 40px;
    height: 40px;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
}

.medallion-emoji {
    font-size: 1.85rem;
    line-height: 1;
    filter: drop-shadow(0 3px 5px rgba(0, 0, 0, 0.25));
}

/* Card Body */
.shop-card-body {
    padding: 0.45rem 0.65rem 0.6rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.card-name {
    margin: 0 0 0.18rem;
    font-family: var(--font-display);
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--px-ink, #1E1B4B);
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-meta {
    margin: 0 0 0.45rem;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.25;
    color: var(--px-ink-muted, #64748B);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 1.7rem;
}

/* Card Footer: Price & Push Button */
.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    margin-top: auto;
}

.card-price-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    padding: 0.15rem 0.42rem;
    border-radius: 8px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1px solid var(--px-line, #E2E8F0);
}

.price-gem {
    font-size: 0.72rem;
}

.price-val {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-tint-warn-ink, #92400E);
}

.card-btn-pod {
    display: flex;
    align-items: center;
}

/* 3D Tactile Buttons (Zero layout shift) */
.shop-action-btn,
.list-action-btn {
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    font-family: var(--font-display);
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
}

.action-btn-face {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.26rem 0.65rem;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.is-buy-btn .action-btn-face {
    background: linear-gradient(180deg, #FDE047 0%, #F59E0B 100%);
    border: 1.5px solid #F59E0B;
    color: #78350F;
    box-shadow: 0 3px 0 #D97706, inset 0 1px 0 #FFFFFF;
}

.is-equip-btn .action-btn-face {
    background: linear-gradient(180deg, #60A5FA 0%, #3B82F6 100%);
    border: 1.5px solid #2563EB;
    color: #FFFFFF;
    box-shadow: 0 3px 0 #1D4ED8, inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.is-active-btn .action-btn-face {
    background: var(--px-tint-success-bg, #ECFDF5);
    border: 1.5px solid var(--px-tint-success-line, #10B981);
    color: var(--px-tint-success-ink, #065F46);
    box-shadow: 0 1px 0 var(--px-tint-success-line, #059669);
}

.is-locked-btn .action-btn-face {
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-line, #CBD5E1);
    color: var(--px-ink-muted, #64748B);
    box-shadow: 0 2px 0 var(--px-ledge, #94A3B8);
}

.shop-action-btn:active .action-btn-face,
.list-action-btn:active .action-btn-face {
    transform: translateY(2px);
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
}

/* ==========================================================================
   3. VIEW MODE 2: COMPACT LIST ROWS (Matches Classrooms.vue list rows)
   ========================================================================== */
.shop-compact-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.shop-list-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.45rem 0.75rem;
    border-radius: 16px;
    background: linear-gradient(180deg,
        var(--px-surface-card, #FFFFFF) 0%,
        color-mix(in srgb, var(--item-accent, #6366F1) 10%, var(--px-surface-card-bottom, #F8FAFC)) 100%);
    border: 2px solid var(--px-card-border, #E2E8F0);
    box-shadow:
        0 3px 0 var(--item-ledge, #CBD5E1),
        0 6px 14px rgba(var(--px-shadow-rgb), 0.08);
    cursor: pointer;
    transition: transform 0.15s ease;
}

.shop-list-row:hover {
    transform: translateY(-1.5px);
}

.list-medallion {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--item-accent, #6366F1) 20%, var(--px-surface-elevated, #FFFFFF));
    border: 1.5px solid var(--item-accent, #6366F1);
    display: grid;
    place-items: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.list-main-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.list-title-row {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.list-title {
    font-family: var(--font-display);
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--px-ink, #1E1B4B);
}

.list-kicker {
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--px-ink-muted, #64748B);
    background: var(--px-surface-well, #F1F5F9);
    padding: 0.05rem 0.35rem;
    border-radius: 4px;
}

.list-active-tag {
    font-size: 0.58rem;
    font-weight: 800;
    color: var(--px-tint-success-ink, #065F46);
    background: var(--px-tint-success-bg, #ECFDF5);
    padding: 0.05rem 0.35rem;
    border-radius: 4px;
}

.list-stock-tag {
    font-size: 0.58rem;
    font-weight: 800;
    color: var(--px-tint-info-ink, #0369A1);
    background: var(--px-tint-info-bg, #E0F2FE);
    padding: 0.05rem 0.35rem;
    border-radius: 4px;
}

.list-meta {
    font-size: 0.68rem;
    color: var(--px-ink-muted, #64748B);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.list-actions-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.list-price-badge {
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-tint-warn-ink, #92400E);
    background: var(--px-tint-warn-bg, #FEF9C3);
    padding: 0.18rem 0.45rem;
    border-radius: 8px;
    border: 1px solid var(--px-tint-warn-line, #F59E0B);
}

/* Empty State */
.shop-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
    text-align: center;
    background: var(--px-surface-card, #FFFFFF);
    border: 2px dashed var(--px-line, #CBD5E1);
    border-radius: 24px;
    gap: 0.4rem;
}

.empty-emoji {
    font-size: 2.2rem;
}

.empty-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 800;
}

.empty-desc {
    margin: 0;
    font-size: 0.78rem;
    color: var(--px-ink-muted, #64748B);
    max-width: 24rem;
}

.empty-reset-btn {
    margin-top: 0.5rem;
    border: none;
    background: transparent;
    cursor: pointer;
}

.empty-reset-btn .action-btn-face {
    background: linear-gradient(180deg, #6366F1 0%, #4F46E5 100%);
    color: #FFFFFF;
    border: 1.5px solid #4338CA;
    box-shadow: 0 3px 0 #312E81;
}

/* ==========================================================================
   4. COMPACT SQUASH-AND-STRETCH 3D MODAL
   ========================================================================== */
.shop-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: grid;
    place-items: center;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(5px);
    padding: 0.75rem;
}

.shop-inspect-card {
    position: relative;
    width: 100%;
    max-width: 380px;
    border-radius: 24px;
    background: linear-gradient(180deg, var(--px-surface-card, #FFFFFF) 0%, var(--px-surface-card-bottom, #FAF8FF) 100%);
    border: 3px solid var(--px-card-border, #FFFFFF);
    box-shadow:
        0 8px 0 var(--px-ledge, #D2CAEE),
        0 24px 48px -12px rgba(var(--px-shadow-rgb, 15, 23, 42), 0.45);
    color: var(--px-ink, #1E1B4B);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.modal-close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-line, #CBD5E1);
    font-size: 0.75rem;
    font-weight: 900;
    color: var(--px-ink-muted, #64748B);
    cursor: pointer;
    display: grid;
    place-items: center;
    box-shadow: 0 2px 0 var(--px-ledge, #94A3B8);
}

.modal-crest-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.modal-crest-badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #FEF08A 0%, #F59E0B 100%);
    border: 2px solid #FBBF24;
    display: grid;
    place-items: center;
    font-size: 1.45rem;
    box-shadow: 0 3px 0 #D97706, inset 0 1px 0 #FFFFFF;
    flex-shrink: 0;
}

.modal-crest-meta {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.modal-kicker-pill {
    font-family: var(--font-display);
    font-size: 0.58rem;
    font-weight: 800;
    color: var(--px-tint-magic-ink, #6366F1);
    letter-spacing: 0.05em;
}

.modal-title {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--px-ink, #1E1B4B);
    line-height: 1.1;
}

.modal-summary {
    margin: 0;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--px-ink-muted, #475569);
    line-height: 1.35;
}

.modal-perk-box {
    background: var(--px-surface-well, #F8FAFC);
    border: 1.5px solid var(--px-line, #E2E8F0);
    border-radius: 14px;
    padding: 0.55rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.perk-label {
    font-family: var(--font-display);
    font-size: 0.62rem;
    font-weight: 800;
    color: var(--px-tint-warn-ink, #D97706);
}

.perk-body {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--px-ink, #1E293B);
    line-height: 1.3;
}

.modal-ledger-box {
    background: var(--px-tint-warn-bg, #FFFBEB);
    border: 1.5px solid var(--px-tint-warn-line, #FDE68A);
    border-radius: 14px;
    padding: 0.55rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.ledger-line {
    display: flex;
    justify-content: space-between;
    font-size: 0.72rem;
    color: var(--px-tint-warn-ink, #92400E);
}

.ledger-divider {
    height: 1px;
    background: var(--px-tint-warn-line, #FCD34D);
    margin: 0.15rem 0;
}

.ledger-net {
    font-weight: 800;
}

.text-cost {
    color: var(--px-tint-warn-ink, #B45309);
}

.text-danger {
    color: var(--px-tint-danger-ink, #DC2626) !important;
}

.modal-actions-bar {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.modal-secondary-btn {
    flex: 1;
    padding: 0.5rem;
    border-radius: 12px;
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-line, #CBD5E1);
    font-family: var(--font-display);
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--px-ink-muted, #64748B);
    cursor: pointer;
    box-shadow: 0 2px 0 var(--px-ledge, #94A3B8);
}

.modal-primary-btn {
    flex: 2;
    padding: 0.5rem;
    border-radius: 12px;
    font-family: var(--font-display);
    font-size: 0.8rem;
    font-weight: 800;
    cursor: pointer;
    border: none;
    box-shadow: 0 3px 0 rgba(0, 0, 0, 0.2);
}

.is-confirm-buy {
    background: linear-gradient(180deg, #FDE047 0%, #F59E0B 100%);
    border: 1.5px solid #F59E0B;
    color: #78350F;
    box-shadow: 0 3px 0 #D97706;
}

.is-equip-action {
    background: linear-gradient(180deg, #60A5FA 0%, #3B82F6 100%);
    border: 1.5px solid #2563EB;
    color: #FFFFFF;
    box-shadow: 0 3px 0 #1D4ED8;
}

.is-equipped-state {
    background: var(--px-tint-success-bg, #ECFDF5);
    border: 1.5px solid var(--px-tint-success-line, #10B981);
    color: var(--px-tint-success-ink, #065F46);
}

.is-need-more {
    background: var(--px-surface-well, #F1F5F9);
    border: 1.5px solid var(--px-line, #CBD5E1);
    color: var(--px-ink-muted, #64748B);
    box-shadow: 0 3px 0 var(--px-ledge, #94A3B8);
}

/* ==========================================================================
   ANIMATIONS & TRANSITIONS
   ========================================================================== */
@keyframes card-pop {
    0% { transform: scale(0.9); opacity: 0; }
    60% { transform: scale(1.02); opacity: 1; }
    100% { transform: scale(1); }
}

@keyframes toast-slide {
    0% { transform: translateY(-8px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

.px-modal-enter-active {
    transition: opacity 0.22s ease, transform 0.25s var(--spring-bounce);
}

.px-modal-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.px-modal-enter-from,
.px-modal-leave-to {
    opacity: 0;
    transform: scale(0.92);
}

/* ==========================================================================
   RESPONSIVE DESIGN (MOBILE-FIRST COMPACTNESS)
   ========================================================================== */
@media (max-width: 640px) {
    /* Two-column compact card grid on mobile screens so items are visible without endless scroll */
    .shop-compact-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .shop-card {
        border-radius: 16px;
    }

    .card-scene {
        height: 48px;
        margin: 4px 4px 0;
        border-radius: 12px;
    }

    .card-medallion {
        width: 34px;
        height: 34px;
    }

    .mini-art-svg {
        width: 32px;
        height: 32px;
    }

    .medallion-emoji {
        font-size: 1.5rem;
    }

    .scene-kicker {
        font-size: 0.52rem;
        padding: 0.08rem 0.32rem;
    }

    .scene-badge,
    .scene-stock,
    .scene-active-pill {
        font-size: 0.5rem;
        padding: 0.08rem 0.32rem;
    }

    .shop-card-body {
        padding: 0.35rem 0.5rem 0.5rem;
    }

    .card-name {
        font-size: 0.8rem;
    }

    .card-meta {
        font-size: 0.62rem;
        min-height: 1.55rem;
        margin-bottom: 0.35rem;
    }

    .card-price-badge {
        padding: 0.12rem 0.32rem;
    }

    .price-val {
        font-size: 0.72rem;
    }

    .action-btn-face {
        padding: 0.22rem 0.5rem;
        font-size: 0.68rem;
    }

    /* Stack or compact header rows */
    .deck-top-row {
        gap: 0.4rem;
    }

    .deck-right-pod {
        width: 100%;
        justify-content: space-between;
    }

    .deck-search-wrap {
        width: 100%;
    }

    .clay-search-compact {
        max-width: none;
    }
}

@media (max-width: 420px) {
    .deck-title {
        font-size: 1.1rem;
    }

    .vault-value {
        font-size: 0.85rem;
    }

    .vault-lbl {
        display: none;
    }

    .view-btn span {
        display: none;
    }

    .view-btn {
        padding: 0.22rem 0.4rem;
    }

    .shop-list-row {
        padding: 0.35rem 0.55rem;
        gap: 0.45rem;
    }

    .list-medallion {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .list-title {
        font-size: 0.8rem;
    }

    .list-kicker {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .shop-card,
    .shop-list-row,
    .shop-toast-banner {
        animation: none !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
