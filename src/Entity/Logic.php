<?php

namespace App\Entity;

use App\Repository\LogicRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogicRepository::class)
 */
class Logic
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Question::class, inversedBy="logics")
     * @ORM\JoinColumn(nullable=false)
     */
    private $question;

    /**
     * @ORM\Column(type="array")
     */
    private $if_action = [];

    /**
     * @ORM\Column(type="array")
     */
    private $then_action = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIfAction(): ?array
    {
        return $this->if_action;
    }

    public function setIfAction(array $if_action): self
    {
        $this->if_action = $if_action;

        return $this;
    }

    public function getThenAction(): ?array
    {
        return $this->then_action;
    }

    public function setThenAction(array $then_action): self
    {
        $this->then_action = $then_action;

        return $this;
    }
}
