module.exports = {
  content: ["./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}"],
  theme: {
    extend: {
      colors: {
        primary: '#FF6B6B', // Coral pink
        secondary: '#4ECDC4', // Aqua
        accent: '#FFE66D', // Yellow
        neutral: '#FAF9F6', // Off-white
      },
      fontFamily: {
        display: ['Poppins', 'sans-serif'],
        body: ['Nunito', 'sans-serif'],
      },
    },
  },
  plugins: [],
}