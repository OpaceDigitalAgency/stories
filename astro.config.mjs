// @ts-check
import { defineConfig } from 'astro/config';
import tailwind from "@astrojs/tailwind";
import netlify from '@astrojs/netlify';

// https://astro.build/config
export default defineConfig({
  integrations: [
    tailwind() // Use default config
  ],
  adapter: netlify(),
  output: "server", // Revert to server mode for dynamic content
  site: 'https://storiesfromtheweb.netlify.app',
  base: '/',
  // Ensure public directory is properly handled
  publicDir: 'public'
});