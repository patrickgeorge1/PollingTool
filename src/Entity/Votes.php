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
    private $pollId;

    /**
     * @ORM\Column(type="integer")
     */
    private $personId;

    /**
     * @ORM\Column(type="integer")
     */
    private $vote;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPollId(): ?int
    {
        return $this->pollId;
    }

    public function setPollId(int $pollId): self
    {
        $this->pollId = $pollId;

        return $this;
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function setPersonId(int $personId): self
    {
        $this->personId = $personId;

        return $this;
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
}
