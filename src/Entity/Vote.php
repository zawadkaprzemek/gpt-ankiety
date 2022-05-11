<?php

namespace App\Entity;

use App\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=VoteRepository::class)
 */
class Vote
{
    use TimestampableEntity;

    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SessionUser::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sessionUser;

    /**
     * @ORM\ManyToOne(targetEntity=Question::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $question;

    /**
     * @ORM\Column(type="array")
     */
    private $answer = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionUser(): ?SessionUser
    {
        return $this->sessionUser;
    }

    public function setSessionUser(?SessionUser $sessionUser): self
    {
        $this->sessionUser = $sessionUser;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?array
    {
        return $this->answer;
    }

    public function setAnswer(array $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
}
