<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=QuestionRepository::class)
 */
class Question
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=QuestionType::class, inversedBy="questions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort;

    /**
     * @ORM\ManyToOne(targetEntity=Polling::class, inversedBy="questions", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $polling;

    /**
     * @ORM\ManyToOne(targetEntity=Page::class, inversedBy="questions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $page;

    /**
     * @ORM\Column(type="boolean")
     */
    private $required;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $minValText;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $middleValText;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $maxValText;

    /**
     * @ORM\OneToMany(targetEntity=Answer::class, mappedBy="question", cascade={"persist", "remove"})
     */
    private $answers;

    /**
     * @ORM\OneToMany(targetEntity=Vote::class, mappedBy="question")
     */
    private $votes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $deleted=0;

    /**
     * @ORM\OneToMany(targetEntity=Logic::class, mappedBy="question")
     */
    private $logics;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->logics = new ArrayCollection();
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

    public function getType(): ?QuestionType
    {
        return $this->type;
    }

    public function setType(?QuestionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

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

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getMinValText(): ?string
    {
        return $this->minValText;
    }

    public function setMinValText(?string $minValText): self
    {
        $this->minValText = $minValText;

        return $this;
    }

    public function getMiddleValText(): ?string
    {
        return $this->middleValText;
    }

    public function setMiddleValText(?string $middleValText): self
    {
        $this->middleValText = $middleValText;

        return $this;
    }

    public function getMaxValText(): ?string
    {
        return $this->maxValText;
    }

    public function setMaxValText(?string $maxValText): self
    {
        $this->maxValText = $maxValText;

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Vote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setQuestion($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getQuestion() === $this) {
                $vote->setQuestion(null);
            }
        }

        return $this;
    }

    public function decreaseSort():self
    {
        $this->sort--;
        return $this;
    }

    public function increaseSort():self
    {
        $this->sort++;
        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return Collection<int, Logic>
     */
    public function getLogics(): Collection
    {
        return $this->logics;
    }

    public function addLogic(Logic $logic): self
    {
        if (!$this->logics->contains($logic)) {
            $this->logics[] = $logic;
            $logic->setQuestion($this);
        }

        return $this;
    }

    public function removeLogic(Logic $logic): self
    {
        if ($this->logics->removeElement($logic)) {
            // set the owning side to null (unless already changed)
            if ($logic->getQuestion() === $this) {
                $logic->setQuestion(null);
            }
        }

        return $this;
    }

    public function __clone()
    {
        $this->id=null;
        $this->createdAt= new \DateTime();
    }
}
