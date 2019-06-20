<?php


namespace App\Service;


use App\Repository\VotesRepository;

class VotesService
{
    public function votePermission(VotesRepository $votesRepository, $user_id, $poll_id) {
        return ($votesRepository->findOneBy(["pollId" => $poll_id, "personId" => $user_id]) == null);
    }
}