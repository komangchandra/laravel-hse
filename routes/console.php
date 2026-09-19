<?php

use App\Models\PermitIssuance;
use App\Models\User;
use App\Services\PermitIssuanceService;
use App\Services\WorkflowNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('permits:expire', function (PermitIssuanceService $issuances, WorkflowNotificationService $notifications) {
    PermitIssuance::query()->with('application')->where('status', 'active')
        ->whereBetween('expires_at', [today(), today()->addDays(30)])
        ->eachById(fn (PermitIssuance $issuance) => $notifications->expiring($issuance));
    $count = $issuances->expireDue();
    $this->info("{$count} permit ditandai kedaluwarsa.");

    return 0;
})->purpose('Expire active permits whose validity period has ended');

Schedule::command('permits:expire')->dailyAt('00:10')->withoutOverlapping();

Artisan::command('app:bootstrap-developer {email} {--name=Developer} {--generate-password}', function () {
    if (! Role::where('name', 'developer')->exists()) {
        $this->error('Role developer belum tersedia. Jalankan php artisan db:seed terlebih dahulu.');

        return 1;
    }

    $passwordWasGenerated = (bool) $this->option('generate-password');
    $password = $passwordWasGenerated
        ? str_shuffle(Str::lower(Str::random(8)).Str::upper(Str::random(8)).random_int(0, 9).'!')
        : $this->secret('Password');
    $confirmation = $passwordWasGenerated
        ? $password
        : $this->secret('Confirm password');

    if ($password !== $confirmation) {
        $this->error('Konfirmasi password tidak cocok.');

        return 1;
    }

    $validator = Validator::make([
        'email' => $this->argument('email'),
        'name' => $this->option('name'),
        'password' => $password,
    ], [
        'email' => ['required', 'email'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', Password::defaults()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::updateOrCreate(
        ['email' => $this->argument('email')],
        [
            'name' => $this->option('name'),
            'password' => Hash::make($password),
            'partner_id' => null,
            'is_active' => true,
            'deactivated_at' => null,
        ]
    );

    $user->syncRoles(['developer']);

    if (! $user->wasRecentlyCreated) {
        $user->increment('session_version');
        DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
    }

    $this->info('Akun developer berhasil dibuat atau diperbarui.');

    if ($passwordWasGenerated) {
        $this->newLine();
        $this->line('Password baru: '.$password);
        $this->warn('Simpan password tersebut sekarang. Password hanya ditampilkan satu kali.');
    }

    return 0;
})->purpose('Create or update a developer account');
