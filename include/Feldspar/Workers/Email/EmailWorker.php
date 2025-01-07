<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Closure;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;

class EmailWorker
{
    protected Closure $addressFactory;
    protected array $config;

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function send(array $user, string $subject, string $body): void
    {
        $toAddress = new Address($user['email'], $user['name']);

        $systemEmail = $this->config['mail']['systemEmail'];
        $systemName = $this->config['mail']['systemName'];
        $fromAddress = new Address($systemEmail, $systemName);

        $transport = Transport::fromDsn($this->config['mail']['dsn']);
        $mailer = new Mailer($transport);

        $message = (new Email())
            ->to($toAddress)
            ->from($fromAddress)
            ->subject($subject)
            ->text($body);

        $mailer->send($message);
    }
}
