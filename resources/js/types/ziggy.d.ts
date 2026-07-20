/**
 * Type declarations for Ziggy route() global.
 *
 * The actual implementation lives in vendor/tightenco/ziggy (PHP-side) and
 * vendor/tightenco/ziggy/dist/index.esm.js (JS-side). At runtime Ziggy
 * injects `window.route` and Laravel's Vite setup also exposes `route` as
 * a global. We declare the global here so TypeScript can resolve calls
 * like `route('users.show', 1)` without an explicit import.
 *
 * NOTE: The signature is intentionally permissive — we accept any args
 * and return `string` (Ziggy's default behaviour). For strong typing of
 * named routes, install `@types/ziggy-js` and replace this declaration.
 */
declare function route(name: string, params?: unknown): string;

declare global {
    const route: typeof globalThis.route;
}
