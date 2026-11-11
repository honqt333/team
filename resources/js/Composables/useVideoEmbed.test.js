import { describe, it, expect } from 'vitest';
import { resolveVideoEmbed } from './useVideoEmbed';

/**
 * The video-URL resolution that the help centre used to keep in three
 * copies. The test pins the behaviour of the ORIGINAL implementation, so
 * the extraction cannot silently change what a user sees.
 */
describe('resolveVideoEmbed', () => {
    it('returns null for an empty or non-string value', () => {
        expect(resolveVideoEmbed(null)).toBeNull();
        expect(resolveVideoEmbed(undefined)).toBeNull();
        expect(resolveVideoEmbed('')).toBeNull();
        expect(resolveVideoEmbed(123)).toBeNull();
        expect(resolveVideoEmbed({})).toBeNull();
    });

    it('returns null for a URL that is not a video', () => {
        expect(resolveVideoEmbed('https://example.com/page')).toBeNull();
    });

    describe('YouTube', () => {
        it('resolves a watch URL', () => {
            expect(resolveVideoEmbed('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toEqual({
                type: 'youtube',
                src: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
            });
        });

        it('resolves a youtu.be short URL', () => {
            expect(resolveVideoEmbed('https://youtu.be/dQw4w9WgXcQ')).toEqual({
                type: 'youtube',
                src: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
            });
        });

        it('resolves a shorts URL', () => {
            expect(resolveVideoEmbed('https://www.youtube.com/shorts/dQw4w9WgXcQ')).toEqual({
                type: 'youtube',
                src: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
            });
        });

        it('resolves an embed URL', () => {
            expect(resolveVideoEmbed('https://www.youtube.com/embed/dQw4w9WgXcQ')).toEqual({
                type: 'youtube',
                src: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
            });
        });

        it('resolves a live URL', () => {
            expect(resolveVideoEmbed('https://www.youtube.com/live/dQw4w9WgXcQ')).toEqual({
                type: 'youtube',
                src: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
            });
        });

        it('uses the privacy-preserving host', () => {
            expect(resolveVideoEmbed('https://www.youtube.com/watch?v=dQw4w9WgXcQ').src).toContain(
                'youtube-nocookie.com'
            );
        });
    });

    describe('Vimeo', () => {
        it('resolves a plain Vimeo URL', () => {
            expect(resolveVideoEmbed('https://vimeo.com/123456789')).toEqual({
                type: 'vimeo',
                src: 'https://player.vimeo.com/video/123456789?autoplay=1',
            });
        });

        it('resolves a channel Vimeo URL', () => {
            expect(resolveVideoEmbed('https://vimeo.com/channels/staffpicks/123456789')).toEqual({
                type: 'vimeo',
                src: 'https://player.vimeo.com/video/123456789?autoplay=1',
            });
        });
    });

    describe('direct files', () => {
        it.each(['mp4', 'webm', 'ogg'])('resolves a .%s file', (ext) => {
            const url = `https://example.com/clip.${ext}`;
            expect(resolveVideoEmbed(url)).toEqual({ type: 'video', src: url });
        });

        it('resolves a file URL with a query string', () => {
            const url = 'https://example.com/clip.mp4?token=abc';
            expect(resolveVideoEmbed(url)).toEqual({ type: 'video', src: url });
        });
    });

    describe('already-embedded players', () => {
        it('keeps a player URL as-is', () => {
            const url = 'https://player.example.com/embed/xyz';
            expect(resolveVideoEmbed(url)).toEqual({ type: 'iframe', src: url });
        });
    });

    it('trims surrounding whitespace', () => {
        expect(resolveVideoEmbed('  https://vimeo.com/123456789  ')).toEqual({
            type: 'vimeo',
            src: 'https://player.vimeo.com/video/123456789?autoplay=1',
        });
    });
});
