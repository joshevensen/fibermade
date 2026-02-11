-- RedefineTables
PRAGMA defer_foreign_keys=ON;
PRAGMA foreign_keys=OFF;
CREATE TABLE "new_FibermadeConnection" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "shop" TEXT NOT NULL,
    "fibermadeApiToken" TEXT NOT NULL,
    "fibermadeIntegrationId" INTEGER NOT NULL,
    "connectedAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "initialImportStatus" TEXT NOT NULL DEFAULT 'pending',
    "initialImportProgress" TEXT
);
INSERT INTO "new_FibermadeConnection" ("connectedAt", "fibermadeApiToken", "fibermadeIntegrationId", "id", "shop") SELECT "connectedAt", "fibermadeApiToken", "fibermadeIntegrationId", "id", "shop" FROM "FibermadeConnection";
DROP TABLE "FibermadeConnection";
ALTER TABLE "new_FibermadeConnection" RENAME TO "FibermadeConnection";
CREATE UNIQUE INDEX "FibermadeConnection_shop_key" ON "FibermadeConnection"("shop");
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;
