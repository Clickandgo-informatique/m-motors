<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un utilisateur avec ce mail')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;
    // ----------------- CHAMPS DE BASE -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’email est obligatoire')]
    #[Assert\Email(message: 'Le format de l’email n’est pas valide')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nickname = null;

    // ----------------- RELATIONS -----------------
    #[ORM\OneToMany(mappedBy: "user", targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Customer::class, cascade: ['persist', 'remove'])]
    private ?Customer $customer = null;

    // ----------------- 2FA -----------------
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $google2FASecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $is2FAEnabled = false;

    private bool $isTwoFactorVerified = false;

    // ----------------- CONSTRUCTEUR -----------------
    public function __construct()
    {
        $this->favorites = new ArrayCollection();
        $this->setRoles(['ROLE_USER']);
    }

    // ----------------- BASIC -----------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_unique([...$this->roles, 'ROLE_USER']);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // ----------------- NICKNAME -----------------
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;
        return $this;
    }

    // ----------------- FAVORITES -----------------
    /**
     * @return Collection|Favorite[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setUser($this);
        }
        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }
        return $this;
    }

    // ----------------- RELATION CUSTOMER -----------------
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        if ($customer && $customer->getUser() !== $this) {
            $customer->setUser($this);
        }
        return $this;
    }

    // ----------------- 2FA -----------------
    public function getGoogle2FASecret(): ?string
    {
        return $this->google2FASecret;
    }

    public function setGoogle2FASecret(?string $secret): self
    {
        $this->google2FASecret = $secret;
        return $this;
    }

    public function is2FAEnabled(): bool
    {
        return $this->is2FAEnabled;
    }

    public function setIs2FAEnabled(bool $enabled): self
    {
        $this->is2FAEnabled = $enabled;
        return $this;
    }

    public function isTwoFactorVerified(): bool
    {
        return $this->isTwoFactorVerified;
    }

    public function setIsTwoFactorVerified(bool $verified): self
    {
        $this->isTwoFactorVerified = $verified;
        return $this;
    }
}
