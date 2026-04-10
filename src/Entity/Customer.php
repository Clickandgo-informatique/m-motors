<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customer')]
#[UniqueEntity(
    fields: ['email'],
    message: 'Il existe déjà un client avec ce mail.'
)]
#[UniqueEntity(
    fields: ['customerCode'],
    message: 'Ce code client existe déjà.'
)]
#[ORM\HasLifecycleCallbacks]
class Customer
{
    use TimestampableTrait;

    // ================================
    // IDENTIFIANT TECHNIQUE
    // ================================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ================================
    // CODE CLIENT
    // ================================
    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le code client est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 10,
        minMessage: 'Minimum {{ limit }} caractères',
        maxMessage: 'Maximum {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z]{3}-?[0-9]+$/',
        message: 'Format attendu : DUP001 ou DUP-001'
    )]
    private ?string $customerCode = null;

    // ================================
    // INFOS CLIENT
    // ================================
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(max: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L’email est obligatoire')]
    #[Assert\Email(message: 'Email invalide')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    // ================================
    // USER (OBLIGATOIRE)
    // ================================
    #[ORM\OneToOne(
        inversedBy: 'customer',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ================================
    // DOSSIERS
    // ================================
    #[ORM\OneToMany(
        mappedBy: 'customer',
        targetEntity: Dossier::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $dossiers;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();      
    }

    // ================================
    // GETTERS / SETTERS
    // ================================

    public function getId(): ?int
    {
        return $this->id;
    }

    // -------- CODE CLIENT --------
    public function getCustomerCode(): ?string
    {
        return $this->customerCode;
    }

    public function setCustomerCode(string $customerCode): static
    {
        $this->customerCode = $customerCode;
        return $this;
    }

    // -------- FIRSTNAME --------
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);
        return $this;
    }

    // -------- LASTNAME --------
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = strtoupper(trim($lastName));
        return $this;
    }

    // -------- EMAIL --------
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    // ================================
    // USER RELATION
    // ================================
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        if ($user->getCustomer() !== $this) {
            $user->setCustomer($this);
        }

        return $this;
    }

    // ================================
    // DOSSIERS RELATION
    // ================================
    /**
     * @return Collection<int, Dossier>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): static
    {
        if (!$this->dossiers->contains($dossier)) {
            $this->dossiers->add($dossier);
            $dossier->setCustomer($this);
        }

        return $this;
    }

    public function removeDossier(Dossier $dossier): static
    {
        $this->dossiers->removeElement($dossier);

        return $this;
    }

    // ================================
    // DEBUG
    // ================================
    public function __toString(): string
    {
        return sprintf(
            '%s %s (%s)',
            $this->firstName,
            $this->lastName,
            $this->customerCode
        );
    }
}
