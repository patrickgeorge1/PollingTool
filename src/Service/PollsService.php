<?php


namespace App\Service;


use App\Entity\Votes;
use App\Repository\PollsRepository;
use App\Repository\UsersRepository;
use App\Repository\VotesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\VotesService;
use Symfony\Component\HttpFoundation\Response;

class PollsService
{
    public function vote (EntityManagerInterface $entityManager, $vote, $pollId, $actor, PollsRepository $pollsRepository, VotesRepository $votesRepository, UsersRepository $usersRepository, VotesService $votesService) {
        // check to see if already voted
        $searchForVote = $votesRepository->findOneBy(["polls"=>$pollId, "users"=>$actor]);



        if ($searchForVote == null) {
            // save vote in db
            $vote_instance = new Votes();
            $user = $usersRepository->findOneBy(["id"=>$actor]);
            $vote_instance->setUsers($user);
            $poll = $pollsRepository->findOneBy(["id"=>$pollId]);
            $vote_instance->setPolls($poll);
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
        } else {
            $vote_now = $votesRepository->findOneBy(["polls" => $pollId,"users" => $actor]);
            $vote_now->setVote(($vote_now->getVote()+1)%2);
            $entityManager->persist($vote_now);
            $entityManager->flush();

            $poll_now = $pollsRepository->findOneBy(["id" => $pollId]);
            if ($vote == 1) {
                $poll_now->setYesVote($poll_now->getYesVote() + 1);
                $poll_now->setNoVote($poll_now->getNoVote() - 1);
            }
            else {
                $poll_now->setYesVote($poll_now->getYesVote() - 1);
                $poll_now->setNoVote($poll_now->getNoVote() + 1);
            }
            $entityManager->persist($poll_now);
            $entityManager->flush();
        }

        return 1;
    }

    public function computeYes(PollsRepository $pollsRepository, $pollId) {
        $row = $pollsRepository -> findOneBy(["ïd" => $pollId]);
        $yesVote = $row->getYesVote();
        $noVotes = $row->getNoVote();
        return ($yesVote/($yesVote + $noVotes))*100;

    }

}