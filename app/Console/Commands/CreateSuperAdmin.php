<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super-admin {name} {email} {--generate-password : Generate and display a one-time password}';

    protected $description = 'Create the first or an additional Super Admin account';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $validator = Validator::make([
            'name' => $this->argument('name'),
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        if ($this->option('generate-password')) {
            $password = Str::password(32);
        } else {
            $password = $this->secret('Password for the new Super Admin');
            $confirmation = $this->secret('Confirm password');

            if (! $password || $password !== $confirmation || strlen($password) < 12) {
                $this->components->error('Passwords must match and contain at least 12 characters.');

                return self::FAILURE;
            }
        }

        Admin::create([
            'name' => trim((string) $this->argument('name')),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->components->info('Super Admin account created.');

        if ($this->option('generate-password')) {
            $this->line('Login email: '.$email);
            $this->line('Generated password: '.$password);
            $this->components->warn('Save this password now. It cannot be retrieved later.');
        }

        return self::SUCCESS;
    }
}
