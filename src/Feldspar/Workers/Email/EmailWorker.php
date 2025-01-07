<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Feldspar\Entities\Account;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailWorker
{
    /**
     * Constructor
     *
     * @param array<string, string> $mailConfig
     */
    public function __construct(
        protected array $mailConfig,
    ) {
    }

    /**
     * Send the email
     *
     * @param Account $account
     * @param string $subject
     * @param string $body
     */
    public function send(Account $account, string $subject, string $body): void
    {
        $toAddress = new Address(
            $account->email,
            join(' ', [$account->firstName, $account->lastName])
        );

        $systemEmail = $this->mailConfig['systemEmail'];
        $systemName = $this->mailConfig['systemName'];
        $fromAddress = new Address($systemEmail, $systemName);

        $transport = Transport::fromDsn($this->mailConfig['dsn']);
        $mailer = new Mailer($transport);

        $message = (new Email())
            ->to($toAddress)
            ->from($fromAddress)
            ->subject($subject)
            ->text($body);

        $mailer->send($message);
    }
}
