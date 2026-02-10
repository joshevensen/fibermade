-- CreateTable
CREATE TABLE "FibermadeConnection" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "shop" TEXT NOT NULL,
    "fibermadeApiToken" TEXT NOT NULL,
    "fibermadeIntegrationId" INTEGER NOT NULL,
    "connectedAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- CreateIndex
CREATE UNIQUE INDEX "FibermadeConnection_shop_key" ON "FibermadeConnection"("shop");
