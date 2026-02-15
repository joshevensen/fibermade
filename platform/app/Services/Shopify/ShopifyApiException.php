<?php

namespace App\Services\Shopify;

use Exception;

class ShopifyApiException extends Exception
{
    /**
     * @param  array<int, mixed>  $rawErrors
     */
    public function __construct(
        string $message,
        private readonly array $rawErrors = []
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, mixed>
     */
    public function getRawErrors(): array
    {
        return $this->rawErrors;
    }
}
