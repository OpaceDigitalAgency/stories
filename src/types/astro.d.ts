// Type definitions for Astro runtime
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

export {};