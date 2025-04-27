# Static Version of Stories From The Web

This directory contains the last working static version of the Stories From The Web site before Strapi CMS integration was added. This version was pulled from commit `1f088ae` with the message "Add reading history and user profile page to complete recommendations system".

## Contents

- `src/pages/index.astro`: The main homepage file
- `src/styles/base.css`: The main CSS file with Tailwind directives and custom styles
- `src/components/`: All the components used in the homepage
- Configuration files:
  - `astro.config.mjs`
  - `postcss.config.js`
  - `tailwind.config.js`
  - `package.json`

## Purpose

This static version can be used as a reference or fallback if issues arise with the Strapi integration. It contains all the necessary files to render the homepage with hardcoded mock data.

## How to Use

To use this static version:

1. Copy the files to the main project directory (or use them as reference)
2. Run `npm install` to install dependencies
3. Run `npm run dev` to start the development server

Note that this version does not have any dynamic data fetching from Strapi, so all content is hardcoded in the components.