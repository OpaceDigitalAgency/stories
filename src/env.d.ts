/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly PUBLIC_API_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

declare namespace Astro {
  interface Locals {
    user?: {
      id: number;
      name: string;
      email: string;
      isAdmin?: boolean;
    };
  }
}