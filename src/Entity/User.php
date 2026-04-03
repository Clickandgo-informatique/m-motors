<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un utilisateur avec ce mail')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
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

    // ----------------- RELATIONS -----------------
    #[ORM\OneToMany(mappedBy: "user", targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nickname = null;

    // ----------------- 2FA -----------------
    #[ORM\Column(length: 255)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $totpEnabled = false;

    // ----------------- CONSTRUCTEUR -----------------
    public function __construct()
    {
        $this->favorites = new ArrayCollection();

        // Générer automatiquement un secret TOTP pour tout nouvel utilisateur
        $this->totpSecret = self::generateTotpSecret();
        $this->totpEnabled = true;
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

    // ----------------- TOTP -----------------
    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }
    public function setTotpSecret(?string $totpSecret): static
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpEnabled && $this->totpSecret !== null;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfiguration
    {
        if (!$this->totpSecret) return null;

        return new TotpConfiguration(
            $this->totpSecret,
            'sha1', // algorithme
            30,     // période en secondes
            6       // nombre de digits
        );
    }

    public function enableTotp(): static
    {
        $this->totpEnabled = true;
        return $this;
    }
    public function disableTotp(): static
    {
        $this->totpEnabled = false;
        return $this;
    }
    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
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

    // ----------------- UTIL -----------------
    private static function generateTotpSecret(int $length = 16): string
    {
        // Génère un secret compatible Base32 (lettres A-Z et chiffres 2-7)
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }
}
