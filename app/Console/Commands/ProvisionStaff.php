<?php

namespace App\Console\Commands;

use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Console\Command;

class ProvisionStaff extends Command
{
    protected $signature = 'kioosk:staff {mobile : Iranian mobile number} {--role=admin : admin or superadmin} {--name= : Staff display name}';

    protected $description = 'Provision a staff account; phone verification is still required at sign-in';

    public function handle(): int
    {
        $mobile = IranianMobile::normalize($this->argument('mobile'));
        $role = PlatformRole::tryFrom($this->option('role'));
        if (! IranianMobile::valid($mobile) || ! in_array($role, [PlatformRole::Admin, PlatformRole::Superadmin], true)) {
            $this->error('شماره موبایل یا نقش معتبر نیست. نقش باید admin یا superadmin باشد.');

            return self::FAILURE;
        }
        $user = User::where('mobile', $mobile)->first() ?? new User;
        $user->mobile = $mobile;
        $user->name = $this->option('name') ?: ($user->name ?: 'همکار کیوسک');
        $user->platform_role = $role;
        $user->save();
        $this->info('حساب همکار ثبت شد. ورود از /admin/login با کد یک‌بارمصرف انجام می‌شود.');

        return self::SUCCESS;
    }
}
