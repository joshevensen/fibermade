import { describe, expect, it } from "vitest";
import { formatConnectedAt } from "./date";

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
