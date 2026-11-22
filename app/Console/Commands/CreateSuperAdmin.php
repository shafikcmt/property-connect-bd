<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'pcbd:create-super-admin {--name=} {--email=}';

    protected $description = 'Create a Super Admin account (no default credentials exist; the password is prompted, never stored in config).';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $name = $this->option('name') ?: text('Name', required: true);
        $email = strtolower($this->option('email') ?: text('Email', required: true));
        $password = password('Password (min 12 characters)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', Password::min(12)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
            $this->components->error('A user with that email already exists.');

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole(RoleName::SuperAdmin->value);

        $this->components->info("Super Admin [{$email}] created. Two-factor setup is required at first sign-in.");

        return self::SUCCESS;
    }
}
