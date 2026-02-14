/**
 * Returns Tailwind badge classes for an order status.
 * Used for consistent status badge styling on OrderIndexPage and OrderEditPage.
 */
export function orderStatusBadgeClass(
    status: string | null | undefined,
): string {
    const key = status?.toLowerCase() ?? '';
    const statusMap: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800',
        open: 'bg-blue-100 text-blue-800',
        accepted: 'bg-indigo-100 text-indigo-800',
        fulfilled: 'bg-amber-100 text-amber-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return statusMap[key] ?? 'bg-gray-100 text-gray-800';
}
