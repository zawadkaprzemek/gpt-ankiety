<?php

namespace App\Entity;

use App\Repository\CodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=CodeRepository::class)
 */
class Code
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $content;

    /**
     * @ORM\Column(type="boolean")
     */
    private $multi;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="codes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=SessionUser::class, mappedBy="code")
     */
    private $sessionUsers;

    /**
     * @ORM\ManyToOne(targetEntity=Polling::class, inversedBy="codes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $polling;

    /**
     * @ORM\Column(type="integer")
     */
    private $usesLimit=1;

    public function __construct()
    {
        $this->sessionUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function isMulti(): ?bool
    {
        return $this->multi;
    }

    public function setMulti(bool $multi): self
    {
        $this->multi = $multi;

        return $this;
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

    /**
     * @return Collection<int, SessionUser>
     */
    public function getSessionUsers(): Collection
    {
        return $this->sessionUsers;
    }

    public function addSessionUser(SessionUser $sessionUser): self
    {
        if (!$this->sessionUsers->contains($sessionUser)) {
            $this->sessionUsers[] = $sessionUser;
            $sessionUser->setCode($this);
        }

        return $this;
    }

    public function removeSessionUser(SessionUser $sessionUser): self
    {
        if ($this->sessionUsers->removeElement($sessionUser)) {
            // set the owning side to null (unless already changed)
            if ($sessionUser->getCode() === $this) {
                $sessionUser->setCode(null);
            }
        }

        return $this;
    }

    public function getPolling(): ?Polling
    {
        return $this->polling;
    }

    public function setPolling(?Polling $polling): self
    {
        $this->polling = $polling;

        return $this;
    }

    public function getUsesLimit(): ?int
    {
        return $this->usesLimit;
    }

    public function setUsesLimit(int $usesLimit): self
    {
        $this->usesLimit = $usesLimit;

        return $this;
    }
}
