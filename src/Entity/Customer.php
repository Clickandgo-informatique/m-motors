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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le code client est obligatoire')]
    #[Assert\Length(min: 3, max: 10)]
    #[Assert\Regex(
        pattern: '/^[A-Z]{3}-?[0-9]+$/',
        message: 'Format attendu : DUP001 ou DUP-001'
    )]
    private ?string $customerCode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(\+33|0)[1-9](\d{2}){4}$/',
        message: 'Le numéro de téléphone 1 doit être valide (ex : +33612345678 ou 0612345678).'
    )]
    private ?string $phoneNumber1 = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(\+33|0)[1-9](\d{2}){4}$/',
        message: 'Le numéro de téléphone 2 doit être valide (ex : +33612345678 ou 0612345678).'
    )]
    private ?string $phoneNumber2 = null;

    #[ORM\OneToOne(
        mappedBy: 'customer',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    private ?User $user = null;

    #[ORM\OneToMany(
        mappedBy: 'customer',
        targetEntity: Dossier::class,
        cascade: ['persist']
    )]
    private Collection $dossiers;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Length(max: 7)]
    #[Assert\Regex(
        pattern: "/^[0-9]{5}$/",
        message: "Le code postal doit contenir exactement 5 chiffres."
    )]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/",
        message: "La ville contient des caractères invalides."
    )]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $addressDetails = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/",
        message: "Le pays contient des caractères invalides."
    )]
    private ?string $country = null;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerCode(): ?string
    {
        return $this->customerCode;
    }

    public function setCustomerCode(string $customerCode): static
    {
        $this->customerCode = strtoupper(trim($customerCode));
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = strtoupper(trim($lastName));
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getPhoneNumber1(): ?string
    {
        return $this->phoneNumber1;
    }

    public function setPhoneNumber1(?string $phoneNumber1): static
    {
        $this->phoneNumber1 = $phoneNumber1 ? preg_replace('/\s+/', '', $phoneNumber1) : null;
        return $this;
    }

    public function getPhoneNumber2(): ?string
    {
        return $this->phoneNumber2;
    }

    public function setPhoneNumber2(?string $phoneNumber2): static
    {
        $this->phoneNumber2 = $phoneNumber2 ? preg_replace('/\s+/', '', $phoneNumber2) : null;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        if ($user && $user->getCustomer() !== $this) {
            $user->setCustomer($this);
        }

        return $this;
    }

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getAddressDetails(): ?string
    {
        return $this->addressDetails;
    }

    public function setAddressDetails(?string $addressDetails): static
    {
        $this->addressDetails = $addressDetails;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country ? trim($country) : null;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s (%s)',
            $this->firstName ?? '',
            $this->lastName ?? '',
            $this->customerCode ?? ''
        );
    }
}
