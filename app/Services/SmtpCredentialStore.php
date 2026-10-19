<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class SmtpCredentialStore
{
    public function password(): ?string
    {
        $disk=Storage::disk('local');
        if (! $disk->exists('mail-smtp.enc')) return null;
        try {
            $data=json_decode(Crypt::decryptString($disk->get('mail-smtp.enc')),true,512,JSON_THROW_ON_ERROR);
            return ($data['username'] ?? null) === config('mail.mailers.smtp.username') ? ($data['password'] ?? null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(string $password): void
    {
        $disk=Storage::disk('local');
        $temporary='mail-smtp-'.bin2hex(random_bytes(8)).'.tmp';
        $encrypted=Crypt::encryptString(json_encode(['username'=>config('mail.mailers.smtp.username'),'password'=>$password],JSON_THROW_ON_ERROR));
        if (! $disk->put($temporary,$encrypted)) throw new \RuntimeException('Credential storage failed.');
        chmod($disk->path($temporary),0600);
        if (! $disk->move($temporary,'mail-smtp.enc')) throw new \RuntimeException('Credential storage failed.');
    }
}
