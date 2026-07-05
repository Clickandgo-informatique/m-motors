<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\EmailStatus;
use App\Enum\EmailType;
use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(
    name: 'email_log',
    indexes: [
        new ORM\Index(name: 'idx_email_log_recipient', columns: ['recipient']),
        new ORM\Index(name: 'idx_email_log_status', columns: ['status']),
        new ORM\Index(name: 'idx_email_log_type', columns: ['type']),
        new ORM\Index(name: 'idx_email_log_sent_at', columns: ['sent_at']),
        new ORM\Index(name: 'idx_email_log_message_id', columns: ['message_id']),
    ]
)]
class EmailLog
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // user métier lié à l'action (optionnel)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    // dossier métier lié à l'email (optionnel)
    #[ORM\ManyToOne(targetEntity: Dossier::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Dossier $dossier = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $sender;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $recipient;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $subject;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $templateName;

    #[ORM\Column(length: 50, enumType: EmailType::class)]
    private EmailType $type;

    #[ORM\Column(length: 50, enumType: EmailStatus::class)]
    private EmailStatus $status;

    // id retourné par le provider SMTP (Mailgun, SES, Brevo, etc.)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $messageId = null;

    // erreur SMTP éventuelle
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    // date réelle d'envoi
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): self
    {
        $this->templateName = $templateName;

        return $this;
    }

    public function getType(): EmailType
    {
        return $this->type;
    }

    public function setType(EmailType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): EmailStatus
    {
        return $this->status;
    }

    public function setStatus(EmailStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}