<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReverbGenerateKey extends Command
{
    protected $signature = 'reverb:generate';

    protected $description = 'Generate Reverb App ID, Key and Secret and save to .env';

    public function handle()
    {
        $appId = random_int(100000, 999999);
        $appKey = Str::random(32);
        $appSecret = Str::random(64);

        $this->setEnvValue('REVERB_APP_ID', $appId);
        $this->setEnvValue('REVERB_APP_KEY', $appKey);
        $this->setEnvValue('REVERB_APP_SECRET', $appSecret);

        $this->info('✅ Reverb keys generated successfully!');
        $this->line("REVERB_APP_ID={$appId}");
        $this->line("REVERB_APP_KEY={$appKey}");
        $this->line("REVERB_APP_SECRET={$appSecret}");

        return self::SUCCESS;
    }

    protected function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env file not found!');

            return;
        }

        $env = file_get_contents($envPath);

        if (preg_match("/^{$key}=.*/m", $env)) {
            // Nếu đã tồn tại → replace
            $env = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $env
            );
        } else {
            // Nếu chưa có → append
            $env .= PHP_EOL."{$key}={$value}";
        }

        file_put_contents($envPath, $env);
    }
}
