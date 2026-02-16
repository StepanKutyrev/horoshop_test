<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_login_pass', columns: ['login', 'pass'])]
#[UniqueEntity(fields: ['login'], message: 'This login is already in use.')]
#[UniqueEntity(fields: ['pass'], message: 'This password is already in use.')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Assert\Length(max: 8)]
    private ?int $id = null;
    #[ORM\Column(length: 8, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $login = null;
    #[ORM\Column(length: 8)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $phone = null;
    #[ORM\Column(length: 8, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $pass = null;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLogin(): ?string
    {
        return $this->login;
    }
    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
    public function getPass(): ?string
    {
        return $this->pass;
    }
    public function setPass(string $pass): self
    {
        $this->pass = $pass;
        return $this;
    }
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }
    public function eraseCredentials(): void
    {
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }
}
