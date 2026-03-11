import { expect, afterEach } from "vitest";
import { cleanup } from "@testing-library/react";
import * as matchers from "@testing-library/jest-dom/matchers";

if (!process.env.PRISMA_FIELD_ENCRYPTION_KEY) {
  process.env.PRISMA_FIELD_ENCRYPTION_KEY =
    "k1.aesgcm256.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=";
}

expect.extend(matchers);
afterEach(() => cleanup());
