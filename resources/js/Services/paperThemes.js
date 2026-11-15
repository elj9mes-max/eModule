/**
 * Paper styles a teacher picks per lecture page in the Studio; students read the page on the same paper.
 * Keep the ids in sync with App\Enums\PaperTheme.
 */
export const paperThemes = [
    { id: 'classic', label: 'Classic White', bg: '#FFFFFF', text: '#0F172A', border: '#E2E8F0', accent: '#2563EB' },
    {
        id: 'parchment',
        label: 'Story Parchment',
        bg: '#FFFDF5',
        text: '#292524',
        border: '#FDE68A',
        accent: '#B45309',
        graphic: 'story',
        icon: '📜',
        chipGradient: 'linear-gradient(135deg, #FFFBEB 0%, #FDE68A 55%, #F59E0B 100%)',
    },
    {
        id: 'emerald',
        label: 'Forest Scroll',
        bg: '#F0FDF4',
        text: '#064E3B',
        border: '#A7F3D0',
        accent: '#059669',
        graphic: 'forest',
        icon: '🌲',
        chipGradient: 'linear-gradient(135deg, #ECFDF5 0%, #A7F3D0 55%, #34D399 100%)',
    },
    {
        id: 'sky',
        label: 'Lagoon Breeze',
        bg: '#F0F9FF',
        text: '#0C4A6E',
        border: '#7DD3FC',
        accent: '#0284C7',
        graphic: 'lagoon',
        icon: '🌊',
        chipGradient: 'linear-gradient(135deg, #CCFBF1 0%, #BAE6FD 52%, #FDE68A 100%)',
    },
    { id: 'lined', label: 'Notebook Lines', bg: '#FFFFFF', text: '#1E293B', border: '#CBD5E1', accent: '#4F46E5', lined: true },
];

export const DEFAULT_PAPER_THEME_ID = 'classic';

/** The paper for a theme id, falling back to Classic White for unknown or missing ids. */
export const findPaperTheme = (id) => paperThemes.find((theme) => theme.id === id) ?? paperThemes[0];
