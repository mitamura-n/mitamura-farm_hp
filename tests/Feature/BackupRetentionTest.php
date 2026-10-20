<?php

namespace Tests\Feature;

use App\Services\BackupRetention;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    public function test_smtp_snapshots_require_matching_hash_before_rotation(): void
    {
        $root=storage_path('framework/testing/retention-'.bin2hex(random_bytes(8)));
        mkdir($root,0700,true);
        try {
            foreach ([1,2,3,4] as $day) {
                $path=$root.'/snapshot-2026010'.$day.'-000000-aaaaaaaa';
                mkdir($path);file_put_contents($path.'/database.sql','SQL');file_put_contents($path.'/environment.env','config');
                file_put_contents($path.'/mail-smtp.enc','encrypted fixture');
                $hashes=['environment.env'=>hash('sha256','config')];
                if ($day!==2) $hashes['mail-smtp.enc']=hash('sha256',$day===3 ? 'corrupt' : 'encrypted fixture');
                file_put_contents($path.'/manifest.json',json_encode(['database'=>'mitamurafarm_prod','sha256'=>hash('sha256','SQL'),'files_sha256'=>$hashes]));
            }
            $this->assertSame(1,(new BackupRetention)->prune($root,1));
            $this->assertDirectoryDoesNotExist($root.'/snapshot-20260101-000000-aaaaaaaa');
            foreach ([2,3,4] as $day) $this->assertDirectoryExists($root.'/snapshot-2026010'.$day.'-000000-aaaaaaaa');
        } finally {
            if (str_starts_with(realpath($root),realpath(storage_path('framework/testing')).DIRECTORY_SEPARATOR)) (new Filesystem)->deleteDirectory($root);
        }
    }
    public function test_rotation_preserves_recent_verified_incomplete_and_unknown_directories(): void
    {
        $root=storage_path('framework/testing/retention-'.bin2hex(random_bytes(8)));
        mkdir($root,0700,true);
        $make=function($day,$extra=[]) use ($root) {
            $path=$root.'/snapshot-202601'.str_pad($day,2,'0',STR_PAD_LEFT).'-000000-aaaaaaaa';
            mkdir($path); file_put_contents($path.'/database.sql','fake SQL'); file_put_contents($path.'/environment.env','fake config');
            file_put_contents($path.'/manifest.json',json_encode($extra+['database'=>'mitamurafarm_prod','sha256'=>hash('sha256','fake SQL'),'restore_verified'=>false]));
            return $path;
        };
        try {
            $old=$make(1); $verified=$make(2,['restore_verified'=>true]); $protected=$make(3,['protected'=>true]);
            $unknown=$make(4); file_put_contents($unknown.'/unexpected.txt','preserve');
            $invalid=$make(5); file_put_contents($invalid.'/database.sql','corrupt');
            $latest=$make(6);$newest=$make(7);
            $this->assertSame(1,(new BackupRetention)->prune($root,2));
            $this->assertDirectoryDoesNotExist($old);
            foreach ([$verified,$protected,$unknown,$invalid,$latest,$newest] as $path) $this->assertDirectoryExists($path);
            $this->assertSame(0,(new BackupRetention)->prune($root,2));
        } finally {
            // Only the freshly created test directory inside workspace storage.
            if (str_starts_with(realpath($root),realpath(storage_path('framework/testing')).DIRECTORY_SEPARATOR)) (new Filesystem)->deleteDirectory($root);
        }
    }
}
