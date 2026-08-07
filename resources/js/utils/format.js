/**
 * Shared formatting helpers (single source of truth).
 */

export function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), sizes.length - 1);
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

export function formatDate(value, options) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    const opts = options ?? date.toLocaleDateString() === date.toLocaleString() ? {} : {};
    try {
        return date.toLocaleString(undefined, opts);
    } catch {
        return date.toLocaleString();
    }
}

export function copyText(text, onCopied) {
    if (!navigator.clipboard) {
        onCopied?.(text);
        return;
    }
    navigator.clipboard.writeText(text).then(() => onCopied?.(text));
}