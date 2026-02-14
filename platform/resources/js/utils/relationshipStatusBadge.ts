/**
 * Returns Tailwind badge classes for a creator-store relationship status.
 * Used for consistent status badge styling on StoreEditPage.
 */
export function relationshipStatusBadgeClass(
    status: string | null | undefined,
): string {
    const key = status?.toLowerCase() ?? '';
    const statusMap: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        paused: 'bg-amber-100 text-amber-800',
        ended: 'bg-gray-100 text-gray-800',
    };
    return statusMap[key] ?? 'bg-gray-100 text-gray-800';
}
