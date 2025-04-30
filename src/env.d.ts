/// <reference types="astro/client" />

declare namespace App {
  interface Locals {
    user?: {
      id: number;
      name: string;
      email: string;
      isAdmin?: boolean;
    };
  }
}

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