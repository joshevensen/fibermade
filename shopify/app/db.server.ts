import { PrismaClient } from "@prisma/client";
import { fieldEncryptionExtension } from "prisma-field-encryption";

declare global {
  // eslint-disable-next-line no-var
  var prismaGlobal: ReturnType<typeof createPrismaClient>;
}

function createPrismaClient() {
  const key = process.env.PRISMA_FIELD_ENCRYPTION_KEY;
  if (!key || key.length < 16) {
    throw new Error(
      "PRISMA_FIELD_ENCRYPTION_KEY is required and must be at least 16 characters. " +
        "Generate one with: npx cloak generate"
    );
  }

  const base = new PrismaClient();
  return base.$extends(fieldEncryptionExtension());
}

if (!global.prismaGlobal) {
  global.prismaGlobal = createPrismaClient();
}

const prisma = global.prismaGlobal;

export default prisma;
