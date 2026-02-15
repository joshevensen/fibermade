export function formatConnectedAt(date: Date): string {
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

const ONE_HOUR_MS = 60 * 60 * 1000;
const ONE_DAY_MS = 24 * ONE_HOUR_MS;

/**
 * Format a sync log timestamp: relative when < 24h ago, otherwise short absolute date/time.
 * Returns "—" for null, undefined, or invalid dates.
 */
export function formatSyncedAt(isoString: string | null | undefined): string {
  if (isoString == null || isoString.trim() === "") return "—";
  const date = new Date(isoString);
  if (Number.isNaN(date.getTime())) return "—";
  const now = Date.now();
  const diffMs = now - date.getTime();
  if (diffMs < 0) return formatSyncedAtAbsolute(date);
  if (diffMs < ONE_DAY_MS) return formatSyncedAtRelative(diffMs);
  return formatSyncedAtAbsolute(date);
}

function formatSyncedAtRelative(diffMs: number): string {
  const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: "auto" });
  if (diffMs < 60 * 1000) return rtf.format(-Math.floor(diffMs / 1000), "second");
  if (diffMs < ONE_HOUR_MS) return rtf.format(-Math.floor(diffMs / (60 * 1000)), "minute");
  return rtf.format(-Math.floor(diffMs / ONE_HOUR_MS), "hour");
}

function formatSyncedAtAbsolute(date: Date): string {
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "short",
    timeStyle: "short",
  }).format(date);
}

/**
 * Human-readable resource type from loggable_type (e.g. "App\\Models\\Colorway" -> "Colorway").
 * Returns "—" when null or empty.
 */
export function formatLoggableType(loggableType: string | null | undefined): string {
  if (loggableType == null || loggableType.trim() === "") return "—";
  const segments = loggableType.split("\\");
  const last = segments[segments.length - 1]?.trim();
  return last ?? "—";
}
