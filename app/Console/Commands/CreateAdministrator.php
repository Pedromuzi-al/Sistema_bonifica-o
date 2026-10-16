<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'app:create-administrator {email} {--name=Administrador}';

    protected $description = 'Cria administrador real com senha oculta e envia a verificação de e-mail';

    public function handle(): int
    {
        $password = $this->secret('Senha do novo administrador (mínimo 12 caracteres)');
        $confirmation = $this->secret('Confirme a senha');
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $password, 'password_confirmation' => $confirmation];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'name' => ['required', 'string', 'max:255'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = DB::transaction(function () use ($data) {
            (new AccessControlSeeder)->run();
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->assignRole('admin');
            AuditLog::create(['causer_id' => $user->id, 'model_type' => User::class, 'model_id' => $user->id, 'action' => 'administrator_bootstrapped']);

            return $user;
        });
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $error) {
            report($error);
            $this->error('Conta criada, mas o envio falhou. Corrija o SMTP, entre com a conta e solicite novo link de confirmação.');

            return self::FAILURE;
        }
        $this->info('Administrador criado. Confirme o endereço pelo link recebido antes de acessar a administração.');

        return self::SUCCESS;
    }
}
