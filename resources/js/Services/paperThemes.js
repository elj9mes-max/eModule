/**
 * Paper styles a teacher picks per lecture page in the Studio; students read the page on the same paper.
 * Keep the ids in sync with App\Enums\PaperTheme.
 */
export const paperThemes = [
    { id: 'classic', label: 'Classic White', bg: '#FFFFFF', text: '#0F172A', border: '#E2E8F0', accent: '#2563EB' },
    { id: 'parchment', label: 'Story Parchment', bg: '#FFFDF5', text: '#292524', border: '#FDE68A', accent: '#B45309' },
    { id: 'emerald', label: 'Forest Scroll', bg: '#F0FDF4', text: '#064E3B', border: '#A7F3D0', accent: '#059669' },
    { id: 'sky', label: 'Lagoon Breeze', bg: '#F0F9FF', text: '#0C4A6E', border: '#BAE6FD', accent: '#0284C7' },
    { id: 'lined', label: 'Notebook Lines', bg: '#FFFFFF', text: '#1E293B', border: '#CBD5E1', accent: '#4F46E5', lined: true },
];

export const DEFAULT_PAPER_THEME_ID = 'classic';

/** The paper for a theme id, falling back to Classic White for unknown or missing ids. */
export const findPaperTheme = (id) => paperThemes.find((theme) => theme.id === id) ?? paperThemes[0];
