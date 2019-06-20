<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VotesRepository")
 */
class Votes
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $vote;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Polls", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $polls;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $users;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVote(): ?int
    {
        return $this->vote;
    }

    public function setVote(int $vote): self
    {
        $this->vote = $vote;

        return $this;
    }

    public function getPolls(): ?Polls
    {
        return $this->polls;
    }

    public function setPolls(?Polls $polls): self
    {
        $this->polls = $polls;

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): self
    {
        $this->users = $users;

        return $this;
    }
}
