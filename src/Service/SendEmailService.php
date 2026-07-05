<?php

namespace App\Service;

use App\Entity\EmailLog;
use App\Enum\EmailStatus;
use App\Enum\EmailType;
use App\Repository\EmailLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class SendEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private EmailLogRepository $emailLogRepository
    ) {}

    public function send(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context = [],
        EmailType $type = EmailType::OTHER,
        ?object $user = null,
        ?object $dossier = null
    ): void {
        $emailLog = new EmailLog();

        $emailLog->setSender($from);
        $emailLog->setRecipient($to);
        $emailLog->setSubject($subject);
        $emailLog->setTemplateName($template);
        $emailLog->setType($type);
        $emailLog->setStatus(EmailStatus::PENDING);

        if ($user !== null && method_exists($emailLog, 'setUser')) {
            $emailLog->setUser($user);
        }

        if ($dossier !== null && method_exists($emailLog, 'setDossier')) {
            $emailLog->setDossier($dossier);
        }

        $this->entityManager->persist($emailLog);
        $this->entityManager->flush();

        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("emails/$template.html.twig")
            ->context($context);

        try {
            $sentMessage = $this->mailer->send($email);

            $emailLog->setStatus(EmailStatus::SENT);
            $emailLog->setSentAt(new \DateTimeImmutable());

            if ($sentMessage !== null && method_exists($sentMessage, 'getMessageId')) {
                $emailLog->setMessageId($sentMessage->getMessageId());
            }
        } catch (TransportExceptionInterface $e) {
            $emailLog->setStatus(EmailStatus::FAILED);
            $emailLog->setFailureReason($e->getMessage());
        }

        $this->entityManager->flush();
    }
}
