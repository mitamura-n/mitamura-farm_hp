<?php

namespace App\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class SmtpConnectionCheck
{
    public function check(string $password): void
    {
        // Fixed destination: do not accept a host or port from browser input.
        $transport=new EsmtpTransport('sv17015.xserver.jp',465,true);
        $transport->setUsername('info@mitamurafarm.com');
        $transport->setPassword($password);
        $transport->setLocalDomain('mitamurafarm.com');
        $transport->getStream()->setTimeout(15);
        try { $transport->start(); } finally {
            // A QUIT failure must not hide the original authentication failure.
            try { $transport->stop(); } catch (\Throwable) {}
        }
    }
}
