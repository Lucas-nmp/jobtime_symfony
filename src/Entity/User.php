<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "integer", unique: true)]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

  
    /**
     * @var string The hashed password
     * @Assert\NotBlank(message="La contraseña no puede estar vacía.")
     * @Assert\Length(
     *     min=8,
     *     minMessage="La contraseña debe tener al menos {{ limit }} caracteres."
     * )
     * @Assert\Regex(
     *     pattern="/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&+-/.,€#])[A-Za-z\d@$!%*?&]+$/",
     *     message="La contraseña debe incluir al menos una letra mayúscula, una minúscula, un número y un carácter especial."
     * )
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 9)]
    private ?string $phone = null;

    #[ORM\Column(type: "float", nullable: false)]
    private float $dailyWorkHours;

    /**
     * @var Collection<int, Signing>
     */
    #[ORM\OneToMany(targetEntity: Signing::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $signing;

    public function __construct()
    {
        $this->signing = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDailyWorkHours(): float
    {
        return $this->dailyWorkHours;
    }

    public function setDailyWorkHours(float $dailyWorkHours): static
    {
        $this->dailyWorkHours = $dailyWorkHours;

        return $this;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection<int, Signing>
     */
    public function getSigning(): Collection
    {
        return $this->signing;
    }

    public function addSigning(Signing $signing): static
    {
        if (!$this->signing->contains($signing)) {
            $this->signing->add($signing);
            $signing->setUser($this);
        }

        return $this;
    }

    public function removeSigning(Signing $signing): static
    {
        if ($this->signing->removeElement($signing)) {
            // set the owning side to null (unless already changed)
            if ($signing->getUser() === $this) {
                $signing->setUser(null);
            }
        }

        return $this;
    }
}
