<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ProductionDatabaseCredentialStore
{
    public function password(): ?string
    {
        $disk=Storage::disk('local');
        if (!$disk->exists('production-db.enc')) return null;
        try {
            $data=json_decode(Crypt::decryptString($disk->get('production-db.enc')),true,512,JSON_THROW_ON_ERROR);
            return ($data['database'] ?? null)==='mitamurafarm_prod' && ($data['username'] ?? null)==='mitamurafarm_prd'
                ? ($data['password'] ?? null) : null;
        } catch (\Throwable) { return null; }
    }

    public function save(string $password): void
    {
        $disk=Storage::disk('local');
        $temporary='production-db-'.bin2hex(random_bytes(8)).'.tmp';
        try {
            $encrypted=Crypt::encryptString(json_encode([
                'database'=>'mitamurafarm_prod','username'=>'mitamurafarm_prd','password'=>$password,
            ],JSON_THROW_ON_ERROR));
            if (!$disk->put($temporary,$encrypted)) throw new \RuntimeException('Private storage failed.');
            if (!chmod($disk->path($temporary),0600)) throw new \RuntimeException('Private permissions failed.');
            if (!$disk->move($temporary,'production-db.enc')) throw new \RuntimeException('Private storage failed.');
        } finally {
            if ($disk->exists($temporary)) $disk->delete($temporary);
        }
    }
}
