module.exports = {
  content: ["./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}"],
  theme: {
    extend: {
      colors: {
        // Primary color scale
        primary: {
          DEFAULT: '#FF5E78', // Vibrant sunset pink
          50: '#FFF2F4',
          100: '#FFE5E9',
          200: '#FFCCD3',
          300: '#FFB3BD',
          400: '#FF99A7',
          500: '#FF5E78',
          600: '#FF2545',
          700: '#EB0023',
          800: '#B8001B',
          900: '#850014'
        },
        // Secondary color scale
        secondary: {
          DEFAULT: '#00C2C7', // Bright ocean blue
          50: '#E6FAFA',
          100: '#CCF5F6',
          200: '#99EBEC',
          300: '#66E0E2',
          400: '#33D6D9',
          500: '#00C2C7',
          600: '#009B9F',
          700: '#007477',
          800: '#004D4F',
          900: '#002627'
        },
        // Accent color scale
        accent: {
          DEFAULT: '#FFD166', // Sunshine yellow
          50: '#FFF9E6',
          100: '#FFF3CC',
          200: '#FFE799',
          300: '#FFDB66',
          400: '#FFD166',
          500: '#FFC533',
          600: '#FFB900',
          700: '#CC9400',
          800: '#996F00',
          900: '#664A00'
        },
        // Tertiary color scale
        tertiary: {
          DEFAULT: '#7B61FF', // Playful purple
          50: '#F5F2FF',
          100: '#EBE5FF',
          200: '#D6CCFF',
          300: '#C2B3FF',
          400: '#AD99FF',
          500: '#7B61FF',
          600: '#4728FF',
          700: '#2600F0',
          800: '#1E00BD',
          900: '#16008A'
        },
        // Success color scale
        success: {
          DEFAULT: '#06D6A0', // Leaf green
          50: '#E6FAF5',
          100: '#CCF5EB',
          200: '#99EBD7',
          300: '#66E0C3',
          400: '#33D6AF',
          500: '#06D6A0',
          600: '#05AB80',
          700: '#048060',
          800: '#035540',
          900: '#022B20'
        },
        // Neutral color scale
        neutral: {
          DEFAULT: '#F9F7FF', // Soft lavender white
          50: '#FFFFFF',
          100: '#F9F7FF',
          200: '#E0D9FF',
          300: '#C7BCFF',
          400: '#AE9EFF',
          500: '#9580FF',
          600: '#6133FF',
          700: '#3D00E6',
          800: '#2E00B3',
          900: '#1F0080'
        },
        // Text colors
        'text-primary': '#2D2B55', // Deep purple-blue
        'text-secondary': '#5D5A88', // Muted purple
        'text-light': '#8E8BB8', // Light purple
      },
      fontFamily: {
        display: ['Poppins', 'sans-serif'],
        body: ['Nunito', 'sans-serif'],
      },
      // Add enhanced box shadow variations for depth and glow effects
      boxShadow: {
        'soft': '0 4px 12px rgba(0, 0, 0, 0.05)',
        'playful': '0 8px 24px rgba(123, 97, 255, 0.15)',
        'card': '0 10px 20px rgba(0, 0, 0, 0.08)',
        'hover': '0 15px 30px rgba(0, 0, 0, 0.12)',
        'glow': '0 0 15px rgba(255, 255, 255, 0.5)',
        '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
      },
      // Add border radius variations
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
        'blob': '60% 40% 40% 60% / 60% 30% 70% 40%',
      },
      // Add 3D transform utilities
      transformStyle: {
        '3d': 'preserve-3d',
      },
      perspective: {
        '1000': '1000px',
      },
      translate: {
        'z-2': '2px',
        'z-4': '4px',
        'z-8': '8px',
        'z-12': '12px',
        'z-16': '16px',
      },
    },
  },
  plugins: [],
}