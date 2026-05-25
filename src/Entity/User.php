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

    // IDENTIFIANT
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // EMAIL
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nickname = null;

    //Gestion du totp secret
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret = null;
    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }
    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    // RELATIONS

    /**
     * Favoris utilisateur
     * orphanRemoval=true permet de supprimer automatiquement les favoris retirés
     */
    #[ORM\OneToMany(
        mappedBy: "user",
        targetEntity: Favorite::class,
        orphanRemoval: true
    )]
    private Collection $favorites;

    #[ORM\OneToOne(inversedBy: 'user')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    // 2FA
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $google2FASecret = null;

    #[ORM\Column]
    private bool $is2FAEnabled = false;

    private bool $isTwoFactorVerified = false;


    public function __construct()
    {
        $this->favorites = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    // BASE USER

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(?int $id): self
    {
        $this->id = $id;

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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
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

    // VERIFICATION EMAIL

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // Customer / Client

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    // Favoris vehicules

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    /**
     * Ajoute un favori et synchronise la relation inverse
     */
    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
        }

        return $this;
    }

    /**
     * Retire un favori
     * orphanRemoval=true gère la suppression automatique
     */
    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // On casse la relation côté propriétaire
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }

    // Google 2FA

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

    // Pseudo / Nickname

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }
    
}
