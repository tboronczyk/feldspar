<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Repositories\EmailContent as EmailContentRepository;
use Slim\Views\Twig;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailWorker
{
    /**
     * @param array $mailConfig
     * @param Twig $twig
     * @param EmailContentRepository $emailContentRepository
     */
    public function __construct(
        protected array $mailConfig,
        protected Twig $twig,
        protected EmailContentRepository $emailContentRepository,
    ) {
    }

    /**
     * @param AccountEntity $account
     * @param string $subject
     * @param string $contentTemplate
     * @param array $data
     */
    public function send(AccountEntity $account, string $subject, string $contentTemplate, array $data): void
    {
        $toAddress = new Address($account->email, $account->firstName . ' ' . $account->lastName);

        $systemEmail = $this->mailConfig['systemEmail'];
        $systemName = $this->mailConfig['systemName'];
        $fromAddress = new Address($systemEmail, $systemName);

        $content = $this->emailContentRepository->fetch($contentTemplate);
        assert($content !== null);

        $htmlContent = $this->twig->fetch('layouts/email.html', [
            'subject' => $subject,
            'content' => $this->twig->fetchFromString($content->html, $data),
        ]);

        $txtContent = $this->twig->fetch('layouts/email.txt', [
            'subject' => $subject,
            'content' => $this->twig->fetchFromString($content->txt, $data),
        ]);

        $transport = Transport::fromDsn($this->mailConfig['dsn']);
        $mailer = new Mailer($transport);

        $message = (new Email())
            ->to($toAddress)
            ->from($fromAddress)
            ->subject($subject)
            ->html($htmlContent)
            ->text($txtContent);

        $mailer->send($message);
    }
}
