/**
 * Purchasable expedition-map trail themes (Gem Bazaar "Trails" category).
 *
 * A theme restyles the S-curve trail stage on the progress page:
 *  - sky: the map viewport background gradient
 *  - tile: an SVG scenery strip that repeats down the (variable-height) map; motifs sit
 *    near the left/right edges so they frame the trail instead of covering it
 *  - ambient: an optional, slowly scrolling particle tile (fireflies, snow, sprinkles…)
 *  - track: stroke colors for the clay trail (rim, base, stepping-stone dashes, glow)
 *
 * Shop items reference a theme by id via metadata.trail_theme.
 */

const svgTile = (width, height, body) =>
    `url("data:image/svg+xml,${encodeURIComponent(
        `<svg xmlns='http://www.w3.org/2000/svg' width='${width}' height='${height}' viewBox='0 0 ${width} ${height}'>${body}</svg>`,
    )}")`;

const pine = (x, y, scale, leaf, dark, snow = null) => `
    <g transform='translate(${x} ${y}) scale(${scale})'>
        <rect x='-4' y='38' width='8' height='14' rx='2' fill='#7C4A2D'/>
        <path d='M0 -6 L22 22 L-22 22 Z' fill='${leaf}'/>
        <path d='M0 8 L26 40 L-26 40 Z' fill='${dark}'/>
        ${snow ? `<path d='M0 -6 L9 6 Q0 10 -9 6 Z' fill='${snow}'/><path d='M0 8 L11 22 Q0 26 -11 22 Z' fill='${snow}'/>` : ''}
    </g>`;

export const trailThemes = {
    'enchanted-forest': {
        label: 'Enchanted Forest',
        sky: 'linear-gradient(180deg, #D9F7D2 0%, #B8EBC0 45%, #9ADBA8 100%)',
        tile: svgTile(400, 520, `
            ${pine(34, 60, 1.1, '#3FA45B', '#2E8049')}
            ${pine(368, 250, 1, '#46B064', '#2F8A4D')}
            ${pine(24, 400, 0.9, '#3FA45B', '#2E8049')}
            <g transform='translate(372 120)'>
                <rect x='-4' y='0' width='8' height='14' rx='3' fill='#FFF4DE'/>
                <path d='M-16 2 Q0 -18 16 2 Z' fill='#EF4444'/>
                <circle cx='-6' cy='-4' r='2.5' fill='#FFFFFF'/><circle cx='5' cy='-7' r='2' fill='#FFFFFF'/>
            </g>
            <g transform='translate(30 230)'>
                <rect x='-3' y='0' width='6' height='10' rx='2' fill='#FFF4DE'/>
                <path d='M-12 2 Q0 -13 12 2 Z' fill='#F59E0B'/>
            </g>
            <g fill='#FDE68A'><circle cx='60' cy='330' r='4'/><circle cx='340' cy='420' r='4'/><circle cx='350' cy='40' r='3.5'/></g>
            <ellipse cx='370' cy='488' rx='30' ry='12' fill='#5BBF73'/>
            <ellipse cx='30' cy='505' rx='26' ry='10' fill='#5BBF73'/>
        `),
        ambient: svgTile(300, 360, `
            <g fill='#FEF08A'>
                <circle cx='40' cy='60' r='2.6'/><circle cx='250' cy='120' r='2.2'/>
                <circle cx='120' cy='230' r='2.4'/><circle cx='280' cy='300' r='2'/>
            </g>
        `),
        ambientMotion: 'rise',
        track: { rim: 'rgba(255, 248, 230, 0.92)', base: '#B48A64', dash: '#F3E2C2', glow: '#FDE68A', shadow: '#2E6B3E' },
        icon: '🌲',
    },
    'sunny-beach': {
        label: 'Sunny Beach',
        sky: 'linear-gradient(180deg, #BDEBFF 0%, #DDF6FF 38%, #FFF1CF 70%, #FBDDA4 100%)',
        tile: svgTile(400, 520, `
            <g transform='translate(36 70)'>
                <path d='M0 70 Q6 30 2 0' fill='none' stroke='#A16207' stroke-width='7' stroke-linecap='round'/>
                <path d='M2 0 Q-26 -10 -38 8 Q-18 0 2 0 Z' fill='#22A06B'/>
                <path d='M2 0 Q30 -12 40 8 Q20 -1 2 0 Z' fill='#16A34A'/>
                <path d='M2 0 Q-8 -26 -28 -26 Q-10 -14 2 0 Z' fill='#34C77B'/>
                <path d='M2 0 Q14 -26 32 -22 Q14 -12 2 0 Z' fill='#2BB673'/>
            </g>
            <g transform='translate(366 200)'>
                <path d='M0 0 C-14 -2 -18 -18 0 -22 C18 -18 14 -2 0 0 Z' fill='#FDA4AF'/>
                <path d='M0 0 L-8 -18 M0 0 L0 -21 M0 0 L8 -18' stroke='#FB7185' stroke-width='2'/>
            </g>
            <g transform='translate(34 330)'>
                <path d='M0 -14 L4 -4 L14 -4 L6 3 L9 13 L0 7 L-9 13 L-6 3 L-14 -4 L-4 -4 Z' fill='#FB923C'/>
            </g>
            <g transform='translate(364 400)'>
                <path d='M0 0 L0 -46' stroke='#F8FAFC' stroke-width='3'/>
                <path d='M-28 -40 Q0 -64 28 -40 Z' fill='#F43F5E'/>
                <path d='M-10 -40 Q0 -62 10 -40 Z' fill='#FDE68A'/>
            </g>
            <path d='M300 90 q10 -6 20 0 t20 0 t20 0' fill='none' stroke='#7DD3FC' stroke-width='3' stroke-linecap='round'/>
            <path d='M40 470 q10 -6 20 0 t20 0 t20 0' fill='none' stroke='#7DD3FC' stroke-width='3' stroke-linecap='round'/>
        `),
        ambient: svgTile(300, 360, `
            <g fill='#FFFFFF' fill-opacity='0.85'>
                <circle cx='60' cy='80' r='3'/><circle cx='230' cy='160' r='2.5'/><circle cx='150' cy='290' r='3'/>
            </g>
        `),
        ambientMotion: 'rise',
        track: { rim: 'rgba(255, 255, 255, 0.95)', base: '#EBC98B', dash: '#FFF7E6', glow: '#FFFFFF', shadow: '#B7791F' },
        icon: '🏝️',
    },
    'candy-land': {
        label: 'Candy Land',
        sky: 'linear-gradient(180deg, #FFE4F1 0%, #F5D9FF 50%, #FFE9D6 100%)',
        tile: svgTile(400, 520, `
            <g transform='translate(36 70)'>
                <rect x='-2.5' y='0' width='5' height='46' rx='2' fill='#FFFFFF'/>
                <circle cx='0' cy='-6' r='18' fill='#F472B6'/>
                <path d='M0 -6 m-12 0 a12 12 0 1 1 12 12 a8 8 0 1 1 -8 -8' fill='none' stroke='#FFFFFF' stroke-width='3.5'/>
            </g>
            <g transform='translate(368 200)'>
                <path d='M-18 10 Q-18 -16 0 -18 Q18 -16 18 10 Z' fill='#34D399'/>
                <ellipse cx='-6' cy='-8' rx='4' ry='2.5' fill='#FFFFFF' fill-opacity='0.7'/>
            </g>
            <g transform='translate(30 320)'>
                <path d='M0 40 L0 0 Q0 -14 12 -14 Q24 -14 24 0' fill='none' stroke='#FFFFFF' stroke-width='8' stroke-linecap='round'/>
                <path d='M0 40 L0 0 Q0 -14 12 -14 Q24 -14 24 0' fill='none' stroke='#EF4444' stroke-width='8' stroke-linecap='round' stroke-dasharray='6 7'/>
            </g>
            <g transform='translate(366 420)'>
                <path d='M-20 0 L-30 -9 L-30 9 Z M20 0 L30 -9 L30 9 Z' fill='#A78BFA'/>
                <ellipse cx='0' cy='0' rx='20' ry='13' fill='#C4B5FD'/>
                <path d='M-8 -12 L-8 12 M4 -13 L4 13' stroke='#8B5CF6' stroke-width='3'/>
            </g>
        `),
        ambient: svgTile(300, 360, `
            <rect x='40' y='60' width='10' height='3.5' rx='1.7' fill='#F472B6' transform='rotate(-30 45 62)'/>
            <rect x='230' y='130' width='10' height='3.5' rx='1.7' fill='#60A5FA' transform='rotate(25 235 132)'/>
            <rect x='120' y='240' width='10' height='3.5' rx='1.7' fill='#FBBF24' transform='rotate(-15 125 242)'/>
            <rect x='260' y='310' width='10' height='3.5' rx='1.7' fill='#34D399' transform='rotate(35 265 312)'/>
        `),
        ambientMotion: 'fall',
        track: { rim: 'rgba(255, 255, 255, 0.95)', base: '#F9B4D6', dash: '#FFFFFF', glow: '#FFF1F8', shadow: '#BE185D' },
        icon: '🍭',
    },
    'frosty-peaks': {
        label: 'Frosty Peaks',
        sky: 'linear-gradient(180deg, #DCEBFF 0%, #EAF4FF 50%, #F6FBFF 100%)',
        tile: svgTile(400, 520, `
            ${pine(34, 60, 1.05, '#3B8F8A', '#2C6E6A', '#FFFFFF')}
            ${pine(368, 250, 0.95, '#3B8F8A', '#2C6E6A', '#FFFFFF')}
            <g transform='translate(368 110)' stroke='#93C5FD' stroke-width='3' stroke-linecap='round'>
                <path d='M0 -14 V14 M-12 -7 L12 7 M-12 7 L12 -7'/>
            </g>
            <g transform='translate(30 360)'>
                <circle cx='0' cy='14' r='16' fill='#FFFFFF' stroke='#BFDBFE' stroke-width='2'/>
                <circle cx='0' cy='-10' r='11' fill='#FFFFFF' stroke='#BFDBFE' stroke-width='2'/>
                <circle cx='-4' cy='-12' r='1.6' fill='#1E293B'/><circle cx='4' cy='-12' r='1.6' fill='#1E293B'/>
                <path d='M0 -9 L8 -7 L0 -6 Z' fill='#F97316'/>
                <path d='M-10 -2 H10' stroke='#EF4444' stroke-width='3' stroke-linecap='round'/>
            </g>
            <g transform='translate(372 450)' stroke='#BFDBFE' stroke-width='2.5' stroke-linecap='round'>
                <path d='M0 -10 V10 M-9 -5 L9 5 M-9 5 L9 -5'/>
            </g>
            <ellipse cx='36' cy='500' rx='34' ry='10' fill='#FFFFFF'/>
        `),
        ambient: svgTile(300, 360, `
            <g fill='#FFFFFF'>
                <circle cx='30' cy='40' r='3'/><circle cx='180' cy='90' r='2.4'/><circle cx='270' cy='170' r='3'/>
                <circle cx='90' cy='220' r='2.6'/><circle cx='220' cy='300' r='2.2'/><circle cx='140' cy='340' r='2.8'/>
            </g>
        `),
        ambientMotion: 'fall',
        track: { rim: 'rgba(255, 255, 255, 0.98)', base: '#B9D7F5', dash: '#F1F7FF', glow: '#E0F2FE', shadow: '#1D4E89' },
        icon: '❄️',
    },
    'space-odyssey': {
        label: 'Space Odyssey',
        sky: 'radial-gradient(circle at 75% 20%, rgba(168, 85, 247, 0.35) 0%, transparent 40%), linear-gradient(180deg, #1E1B4B 0%, #241A5C 50%, #13103A 100%)',
        tile: svgTile(400, 520, `
            <g transform='translate(40 90)'>
                <ellipse cx='0' cy='0' rx='34' ry='8' fill='none' stroke='#FCD34D' stroke-width='3.5' stroke-opacity='0.6' transform='rotate(-16)'/>
                <circle cx='0' cy='0' r='18' fill='#F97316'/>
                <path d='M-34 3 A34 8 0 0 0 34 -3' fill='none' stroke='#FCD34D' stroke-width='3.5' transform='rotate(-16)'/>
            </g>
            <g transform='translate(364 230) rotate(-30)'>
                <path d='M-16 -8 H8 C17 -8 24 0 24 0 C24 0 17 8 8 8 H-16 Z' fill='#F8FAFC'/>
                <circle cx='5' cy='0' r='3.6' fill='#38BDF8'/>
                <path d='M-16 -8 L-25 -16 L-9 -8 Z M-16 8 L-25 16 L-9 8 Z' fill='#F43F5E'/>
                <path d='M-17 -4 L-30 0 L-17 4 Z' fill='#FBBF24'/>
            </g>
            <circle cx='30' cy='380' r='11' fill='#2DD4BF'/>
            <circle cx='26' cy='376' r='3' fill='#99F6E4'/>
            <g transform='translate(360 450)'>
                <circle cx='0' cy='0' r='5' fill='#FDE68A'/>
                <path d='M-4 3 L-40 22' stroke='#FDE68A' stroke-width='3' stroke-linecap='round' stroke-opacity='0.5'/>
            </g>
        `),
        ambient: svgTile(300, 360, `
            <g fill='#FFFFFF'>
                <circle cx='30' cy='30' r='1.6'/><circle cx='160' cy='70' r='1.2'/><circle cx='270' cy='40' r='1.8'/>
                <circle cx='80' cy='160' r='1.4'/><circle cx='210' cy='210' r='1.6'/><circle cx='40' cy='290' r='1.2'/>
                <circle cx='250' cy='330' r='1.5'/><circle cx='140' cy='260' r='1'/>
            </g>
        `),
        ambientMotion: 'twinkle',
        track: { rim: 'rgba(196, 181, 253, 0.55)', base: '#3B2F86', dash: '#C4B5FD', glow: '#E9D5FF', shadow: '#0B0A26' },
        icon: '🪐',
    },
};

export const trailThemeIds = Object.keys(trailThemes);

/**
 * Look up a theme by id; unknown or empty ids fall back to the default trail (null).
 */
export const resolveTrailTheme = (themeId) => (themeId && trailThemes[themeId]) || null;
