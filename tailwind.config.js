// tailwind.config.js
// AINCHORS Website V2 — Design System (Confirmed Brand Tokens)
// Colors sourced directly from official Logo files + live homepage card sampling (verified 19 Aug 2026)
// Typography sourced directly from live site DevTools computed styles (19 Aug 2026)

module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        'ainchors-green':       '#37AD82',
        'ainchors-green-hero':  '#E8FFF7',
        'ainchors-black':       '#000000',
        'ainchors-white':       '#FFFFFF',
        'ainchors-navy':        '#2E3341',
        'ainchors-grey-dark':   '#4E585D',
        'ainchors-grey-light':  '#838383',
        'ainchors-card-blue':   '#C1EFF5',
        'ainchors-card-green':  '#C3F7D4',
        'ainchors-card-orange': '#FCDCBB',
      },
      fontFamily: {
        sans:    ['Inter', 'sans-serif'],
        heading: ['Familjen Grotesk', 'sans-serif'],
      },
      fontSize: {
        'ainchors-h1':   ['45px', { lineHeight: '1.2', fontWeight: '700' }],
        'ainchors-h2':   ['35px', { lineHeight: '1.3', fontWeight: '600' }],
        'ainchors-body': ['15px', { lineHeight: '1.6', fontWeight: '400' }],
      },
      spacing: {
        'logo-gap': '77px',
      },
      borderRadius: {
        'ainchors-card':   '12px',
        'ainchors-button': '8px',
      },
      maxWidth: {
        'ainchors-container': '1280px',
      },
    },
  },
  plugins: [],
}
