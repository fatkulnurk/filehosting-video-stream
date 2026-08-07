/**
 * Shared file/directory helpers (single source of truth).
 */

/**
 * Resolve the primary media object for a file.
 * Prefers the 'default' collection, falls back to the first media item.
 */
export function mediaOf(file) {
    if (!file?.media) return null;
    const media = Array.isArray(file.media) ? file.media : Object.values(file.media);
    if (!media.length) return null;
    return media.find((m) => m.collection_name === 'default') || media[0];
}

export function isVideo(file) {
    const mime = mediaOf(file)?.mime_type || '';
    return mime.startsWith('video/');
}

export function isImage(file) {
    const mime = mediaOf(file)?.mime_type || '';
    return mime.startsWith('image/');
}

export function fileSize(file) {
    return mediaOf(file)?.size || 0;
}

export function typeLabel(file) {
    if (isVideo(file)) return 'Video';
    if (isImage(file)) return 'Image';
    return 'Document';
}

export function mimeOf(file) {
    return mediaOf(file)?.mime_type || '';
}