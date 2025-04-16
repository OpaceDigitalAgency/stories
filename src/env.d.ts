/// <reference types="astro/client" />

interface ImportMetaEnv {
  // Define any environment variables here if needed in the future
  // Currently using hardcoded API_URL in src/lib/api.ts
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}