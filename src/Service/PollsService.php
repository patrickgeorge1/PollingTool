<?php


namespace App\Service;


use App\Entity\Votes;
use App\Repository\PollsRepository;
use App\Repository\VotesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class PollsService
{
    public function vote (EntityManagerInterface $entityManager, $vote, $pollId, $actor, PollsRepository $pollsRepository, VotesRepository $votesRepository) {
        // check to see if already voted
        $searchForVote = $votesRepository->findOneBy(["pollId"=>$pollId, "personId"=>$actor]);
        if ($searchForVote == null) {
            // save vote in db
            $vote_instance = new Votes();
            $vote_instance->setPersonId($actor);
            $vote_instance->setPollId($pollId);
            $vote_instance->setVote($vote);

            // set vote to be count in poll
            $current_poll = $pollsRepository->findOneBy(["id" => $pollId]);
            if($vote == 1) {
                $current_poll->setYesVote($current_poll->getYesVote() + 1);
            }
            else {
                $current_poll->setNoVote($current_poll->getNoVote() + 1);
            }

            // update db
            $entityManager->persist($current_poll);
            $entityManager->persist($vote_instance);
            $entityManager->flush();
        }
        else return 0;

        return 1;
    }

}