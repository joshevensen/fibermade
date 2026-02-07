<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'api:create-token {email}';

    protected $description = 'Create a Sanctum API token for the user with the given email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found for that email.');

            return self::FAILURE;
        }

        $accessToken = $user->createToken('api-token');
        $this->line($accessToken->plainTextToken);

        return self::SUCCESS;
    }
}
