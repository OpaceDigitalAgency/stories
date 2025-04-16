module.exports = {
  content: ["./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}"],
  theme: {
    extend: {
      colors: {
        // Primary palette - brighter, more vibrant
        primary: '#FF5E78', // Vibrant sunset pink
        secondary: '#00C2C7', // Bright ocean blue
        accent: '#FFD166', // Sunshine yellow
        tertiary: '#7B61FF', // Playful purple
        success: '#06D6A0', // Leaf green
        
        // Background colors
        neutral: '#F9F7FF', // Soft lavender white
        'neutral-light': '#FFFFFF',
        'neutral-dark': '#F0EBFF',
        
        // Text colors
        'text-primary': '#2D2B55', // Deep purple-blue
        'text-secondary': '#5D5A88', // Muted purple
        'text-light': '#8E8BB8', // Light purple
      },
      fontFamily: {
        display: ['Poppins', 'sans-serif'],
        body: ['Nunito', 'sans-serif'],
      },
      // Add box shadow variations for depth
      boxShadow: {
        'soft': '0 4px 12px rgba(0, 0, 0, 0.05)',
        'playful': '0 8px 24px rgba(123, 97, 255, 0.15)',
        'card': '0 10px 20px rgba(0, 0, 0, 0.08)',
        'hover': '0 15px 30px rgba(0, 0, 0, 0.12)',
      },
      // Add border radius variations
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
        'blob': '60% 40% 40% 60% / 60% 30% 70% 40%',
      },
    },
  },
  plugins: [],
}