/**
 * Video URL resolution for the help centre.
 *
 * The help centre renders tutorial videos from a URL the user supplies.
 * That URL can be a YouTube watch/shorts/live link, a `youtu.be` short
 * link, a Vimeo link, a direct video file, or an already-embedded player
 * URL. Each of those needs a different `<iframe src>` (or a plain
 * `<video src>`).
 *
 * This logic was duplicated verbatim in three components
 * (`System/Support/Tabs/HelpCenterTab.vue`, `System/Help/Index.vue` and
 * `Help/Index.vue`). It is a pure function with no component state, so it
 * lives here and is shared.
 *
 * @returns {{type: 'youtube'|'vimeo'|'video'|'iframe', src: string}|null}
 *          `null` when the URL is not a video this component can render.
 */
export function resolveVideoEmbed(url) {
    if (!url || typeof url !== 'string') return null;
    const trimmed = url.trim();

    // YouTube: watch?v=ID, youtu.be/ID, embed/ID, shorts/ID, live/ID
    const ytMatch = trimmed.match(
        /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=|shorts\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i
    );
    if (ytMatch && ytMatch[1]) {
        return {
            type: 'youtube',
            src: `https://www.youtube-nocookie.com/embed/${ytMatch[1]}?autoplay=1&rel=0`,
        };
    }

    // Vimeo: vimeo.com/ID
    const vimeoMatch = trimmed.match(
        /vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|video\/|)(\d+)/i
    );
    if (vimeoMatch && vimeoMatch[1]) {
        return {
            type: 'vimeo',
            src: `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`,
        };
    }

    // Direct video formats (.mp4, .webm, .ogg)
    if (/\.(mp4|webm|ogg)($|\?)/i.test(trimmed)) {
        return {
            type: 'video',
            src: trimmed,
        };
    }

    // Already an embed or player URL
    if (trimmed.includes('/embed/') || trimmed.includes('player.')) {
        return {
            type: 'iframe',
            src: trimmed,
        };
    }

    return null;
}

/**
 * Vue composable wrapper, for use inside `<script setup>`.
 */
export function useVideoEmbed() {
    return { getVideoEmbed: resolveVideoEmbed };
}
