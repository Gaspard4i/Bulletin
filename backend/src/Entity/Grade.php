<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\GradeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
#[ORM\Table(name: 'grade')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_TEACHER')"),
        new Get(security: "is_granted('ROLE_TEACHER') or object.getBulletin().getStudent() == user"),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_SCOLARITE')"),
    ],
    normalizationContext: ['groups' => ['grade:read']],
    denormalizationContext: ['groups' => ['grade:write']],
)]
#[ApiResource(
    uriTemplate: '/bulletins/{bulletinId}/grades',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'bulletinId' => new Link(toProperty: 'bulletin', fromClass: Bulletin::class),
    ],
    normalizationContext: ['groups' => ['grade:read']],
    security: "is_granted('ROLE_TEACHER')",
)]
class Grade
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['grade:read', 'bulletin:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Bulletin::class, inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['grade:read', 'grade:write'])]
    private ?Bulletin $bulletin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['grade:read', 'grade:write', 'bulletin:read'])]
    private ?string $subject = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 20)]
    #[Groups(['grade:read', 'grade:write', 'bulletin:read'])]
    private ?float $grade = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['grade:read', 'grade:write', 'bulletin:read'])]
    private ?float $coefficient = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['grade:read', 'grade:write', 'bulletin:read'])]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['grade:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getBulletin(): ?Bulletin
    {
        return $this->bulletin;
    }

    public function setBulletin(?Bulletin $bulletin): static
    {
        $this->bulletin = $bulletin;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getGrade(): ?float
    {
        return $this->grade;
    }

    public function setGrade(float $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): static
    {
        $this->coefficient = $coefficient;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
