<?php

namespace App\Services;

class BackupRetention
{
    public function prune(string $root, int $keep=30): int
    {
        if ($keep<1 || !is_dir($root) || is_link($root)) throw new \RuntimeException('Invalid retention root.');
        $root=realpath($root);
        $eligible=[];
        foreach (scandir($root) as $name) {
            if (!preg_match('/^snapshot-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}$/D',$name)) continue;
            $path=$root.DIRECTORY_SEPARATOR.$name;
            if (is_link($path) || !is_dir($path) || realpath(dirname($path))!==$root) continue;
            $files=array_values(array_diff(scandir($path),['.','..']));
            $allowed=['database.sql','environment.env','manifest.json','dump-error.log'];
            if (array_diff($files,$allowed) || !is_file($path.'/manifest.json') || !is_file($path.'/database.sql') || !is_file($path.'/environment.env')) continue;
            foreach ($files as $file) if (is_link($path.'/'.$file) || !is_file($path.'/'.$file)) continue 2;
            $manifest=json_decode(file_get_contents($path.'/manifest.json'),true);
            if (($manifest['database'] ?? '')!=='mitamurafarm_prod' || !isset($manifest['sha256']) || !hash_equals($manifest['sha256'],hash_file('sha256',$path.'/database.sql'))) continue;
            // Tested restore checkpoints and manually protected sets are never rotated.
            if (!empty($manifest['restore_verified']) || !empty($manifest['protected'])) continue;
            $eligible[$name]=$files;
        }
        krsort($eligible,SORT_STRING);
        $removed=0;
        foreach (array_slice($eligible,$keep,null,true) as $name=>$files) {
            $path=$root.DIRECTORY_SEPARATOR.$name;
            foreach ($files as $file) if (!unlink($path.'/'.$file)) throw new \RuntimeException('Backup rotation failed.');
            if (!rmdir($path)) throw new \RuntimeException('Backup rotation failed.');
            $removed++;
        }
        return $removed;
    }
}
