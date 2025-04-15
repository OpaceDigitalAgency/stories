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
      // Enhanced box shadow variations for depth, glow, and playful effects
      boxShadow: {
        'soft': '0 4px 12px rgba(0, 0, 0, 0.05), 0 2px 6px rgba(0, 0, 0, 0.02), 0 0 10px rgba(255, 255, 255, 0.1)',
        'playful': '0 20px 40px -20px rgba(123, 97, 255, 0.3), 0 10px 20px -10px rgba(123, 97, 255, 0.2), 0 0 15px rgba(123, 97, 255, 0.1)',
        'card': '0 25px 50px -25px rgba(0, 0, 0, 0.15), 0 15px 30px -15px rgba(0, 0, 0, 0.07), 0 0 20px rgba(255, 255, 255, 0.1)',
        'hover': '0 30px 60px -15px rgba(0, 0, 0, 0.2), 0 20px 40px -20px rgba(0, 0, 0, 0.1), 0 0 25px rgba(255, 255, 255, 0.1)',
        'glow': '0 0 30px rgba(255, 255, 255, 0.8), 0 0 20px rgba(255, 255, 255, 0.6), 0 0 10px rgba(255, 255, 255, 0.4)',
        'glow-color': '0 0 30px rgba(var(--glow-color), 0.8), 0 0 20px rgba(var(--glow-color), 0.6), 0 0 10px rgba(var(--glow-color), 0.4)',
        '2xl': '0 40px 70px -20px rgba(0, 0, 0, 0.3), 0 30px 50px -25px rgba(0, 0, 0, 0.2), 0 0 30px rgba(255, 255, 255, 0.1)',
      },
      // Enhanced border radius variations for playful shapes
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
        '4xl': '3rem',
        'blob': '60% 40% 40% 60% / 60% 30% 70% 40%',
        'blob-2': '40% 60% 70% 30% / 50% 60% 40% 50%',
        'blob-3': '70% 30% 50% 50% / 40% 40% 60% 60%',
      },
      // Enhanced 3D transform utilities
      transformStyle: {
        '3d': 'preserve-3d',
      },
      perspective: {
        '500': '500px',
        '1000': '1000px',
        '2000': '2000px',
      },
      translate: {
        'z-2': '2px',
        'z-4': '4px',
        'z-8': '8px',
        'z-12': '12px',
        'z-16': '16px',
        'z-24': '24px',
        'z-32': '32px',
        'z-48': '48px',
        'z-64': '64px',
      },
      rotate: {
        '3d-0': 'rotateY(0deg)',
        '3d-15': 'rotateY(15deg)',
        '3d-30': 'rotateY(30deg)',
        '3d-45': 'rotateY(45deg)',
        '3d-60': 'rotateY(60deg)',
        '3d-90': 'rotateY(90deg)',
        '3d-x-15': 'rotateX(15deg)',
        '3d-x-30': 'rotateX(30deg)',
        '3d-x-45': 'rotateX(45deg)',
        '3d-x-60': 'rotateX(60deg)',
      },
      // Add backdrop blur variations
      backdropBlur: {
        'xs': '2px',
        'sm': '4px',
        DEFAULT: '8px',
        'md': '12px',
        'lg': '16px',
        'xl': '24px',
        '2xl': '40px',
        '3xl': '64px',
      },
      // Add gradient color stops
      gradientColorStops: theme => ({
        ...theme('colors'),
        'white-fade': 'rgba(255, 255, 255, 0)',
      }),
    },
  },
  plugins: [
    require('@tailwindcss/line-clamp'),
    function({ addUtilities }) {
      const newUtilities = {
        '.animation-delay-100': {
          'animation-delay': '100ms',
        },
        '.animation-delay-200': {
          'animation-delay': '200ms',
        },
        '.animation-delay-300': {
          'animation-delay': '300ms',
        },
        '.animation-delay-400': {
          'animation-delay': '400ms',
        },
        '.animation-delay-500': {
          'animation-delay': '500ms',
        },
        '.animation-delay-600': {
          'animation-delay': '600ms',
        },
        '.animation-delay-700': {
          'animation-delay': '700ms',
        },
        '.animation-delay-800': {
          'animation-delay': '800ms',
        },
        '.animation-delay-900': {
          'animation-delay': '900ms',
        },
        '.animation-delay-1000': {
          'animation-delay': '1000ms',
        },
        '.animation-duration-100': {
          'animation-duration': '100ms',
        },
        '.animation-duration-200': {
          'animation-duration': '200ms',
        },
        '.animation-duration-300': {
          'animation-duration': '300ms',
        },
        '.animation-duration-400': {
          'animation-duration': '400ms',
        },
        '.animation-duration-500': {
          'animation-duration': '500ms',
        },
        '.animation-duration-600': {
          'animation-duration': '600ms',
        },
        '.animation-duration-700': {
          'animation-duration': '700ms',
        },
        '.animation-duration-800': {
          'animation-duration': '800ms',
        },
        '.animation-duration-900': {
          'animation-duration': '900ms',
        },
        '.animation-duration-1000': {
          'animation-duration': '1000ms',
        },
      }
      addUtilities(newUtilities)
    }
  ],
}