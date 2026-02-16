import { describe, expect, it } from "vitest";
import {
  formatConnectedAt,
  formatLoggableType,
  formatSyncedAt,
} from "./date";

describe("formatConnectedAt", () => {
  it("formats date with medium dateStyle and short timeStyle", () => {
    const date = new Date("2024-01-15T14:30:00Z");
    const result = formatConnectedAt(date);
    expect(result).toMatch(/\d/);
    expect(result.length).toBeGreaterThan(0);
    expect(typeof result).toBe("string");
  });

  it("returns consistent format for same date", () => {
    const date = new Date("2024-06-20T09:00:00Z");
    const a = formatConnectedAt(date);
    const b = formatConnectedAt(date);
    expect(a).toBe(b);
  });
});

describe("formatSyncedAt", () => {
  it("returns — for null or empty string", () => {
    expect(formatSyncedAt(null)).toBe("—");
    expect(formatSyncedAt(undefined)).toBe("—");
    expect(formatSyncedAt("")).toBe("—");
    expect(formatSyncedAt("   ")).toBe("—");
  });

  it("returns — for invalid date string", () => {
    expect(formatSyncedAt("not-a-date")).toBe("—");
  });

  it("returns relative time for recent date (< 24h)", () => {
    const twoMinutesAgo = new Date(Date.now() - 2 * 60 * 1000).toISOString();
    const result = formatSyncedAt(twoMinutesAgo);
    expect(result).toMatch(/ago|minute|second/i);
  });

  it("returns short absolute date for older date (>= 24h)", () => {
    const oldDate = "2024-01-01T12:00:00.000Z";
    const result = formatSyncedAt(oldDate);
    expect(result).toMatch(/\d/);
    expect(result).not.toMatch(/ago/i);
  });
});

describe("formatLoggableType", () => {
  it("returns — for null or empty", () => {
    expect(formatLoggableType(null)).toBe("—");
    expect(formatLoggableType(undefined)).toBe("—");
    expect(formatLoggableType("")).toBe("—");
    expect(formatLoggableType("   ")).toBe("—");
  });

  it("strips App\\\\Models\\\\ prefix and returns last segment", () => {
    expect(formatLoggableType("App\\Models\\Colorway")).toBe("Colorway");
    expect(formatLoggableType("App\\Models\\Collection")).toBe("Collection");
  });

  it("returns single segment as-is", () => {
    expect(formatLoggableType("Order")).toBe("Order");
  });
});
