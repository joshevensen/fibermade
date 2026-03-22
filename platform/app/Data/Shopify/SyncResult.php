<?php

namespace App\Data\Shopify;

class SyncResult
{
    /**
     * @param  array<int, array{entity_gid: string, message: string}>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
        public array $errors = [],
    ) {}

    public function addError(string $entityGid, string $message): void
    {
        $this->failed++;
        $this->errors[] = ['entity_gid' => $entityGid, 'message' => $message];
    }
}
