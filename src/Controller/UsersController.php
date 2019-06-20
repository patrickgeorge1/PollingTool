<?php

namespace App\Controller;

use App\Entity\Polls;
use App\Entity\Users;
use App\Repository\PollsRepository;
use App\Repository\UsersRepository;
use App\Repository\VotesRepository;
use App\Service\PollsService;
use App\Service\UsersService;
use App\Service\VotesService;
use Doctrine\ORM\EntityManagerInterface;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    private $entityManager;
    private $poolService;

    public function __construct(EntityManagerInterface $entityManager, PollsService $poolService)
    {
        $this->entityManager = $entityManager;
        $this->poolService = $poolService;
    }



    /**
     * @Route("/", name="home", methods={"POST", "GET"})
     */
    public function home() {
        return $this->render(__FUNCTION__.".html.twig");
    }



    /**
     *
     * @Route("/user/add/{key}", name="users_add", methods={"GET", "POST"})
     */
    public function add(Request $request, UsersService $usersService, UsersRepository $usersRepository, $key): Response
    {
        // register a admin or user
        if ($key == 777) $adminStatus = true;
        else $adminStatus = false;
        $email = $request->get("email");

        // check if already exists in database
        if($usersRepository->findOneBy(["email"=>$email]) == null)
        {
            // set new user
            $newUser = new Users();
            $newUser->setEmail($request->get('email'));
            if ($request->get("confirmPassword") != null)
                if ($usersService->verifyPassword($request->get("password"), $request->get("confirmPassword")))
                    $newUser->setPassword(sha1($request->get("password")));
                else
                    return new Response("Invalid Password");
            else
                return new Response("Confirm pass");
            $newUser->setIsAdmin($adminStatus);

            // put the new user to database
            try {
                $this->entityManager->persist($newUser);
                $this->entityManager->flush();
            } catch (\Exception $exception) {
                return new Response($exception->getMessage());
            }

            //return new Response($request->getClientIp());
            return $this->render(__FUNCTION__ . ".html.twig");
        }
        else return new Response("Already a user with this email, go back..");
    }



    /**
     *
     * @Route("/user/admins/list", name="users_admins_list", methods={"GET"})
     */
    public function listAdmins(UsersRepository $usersRepository, Session $session, Request $request): Response
    {
        $adminArray = $usersRepository->findBy(["isAdmin" => true]);
        return $this->render(__FUNCTION__ . '.html.twig', [
            'admins' => $adminArray
        ]);
    }



    /**
     * @Route("/login", name="login", methods={"GET", "POST"})
     */
    public function login(UsersRepository $usersRepository, Request $request, Session $session){
        // pull data from authentication form
        $email = $request->get("email");
        $password = sha1($request->get("password"));
        $isAdmin = $request->get("isAdmin");
        $adminCheck = 0;

        // check role type admin or common user
        if ($isAdmin === "on") {
            $adminCheck = 1;
        }


        // search database for login info
        $login_result = $usersRepository->findOneBy(["email"=>$email, "password"=>$password, "isAdmin"=>$adminCheck]);
        if ($login_result == null) {
            return new Response("User Inexistent");
        }
        else {
            $session->set('user_id', $login_result->getId());
            $session->set('user_email', $login_result->getEmail())  ;
            return $this->redirectToRoute("homepage");
        }  // succesfully logged
    }




    /**
     * @Route("/user/homepage", name="homepage", methods={"GET", "POST"})
     *
     */
    public function homepage(UsersRepository $usersRepository, Session $session) {
        // prevent accessing the route without priviledge protection
        if($session->get('user_id') && $session->get('user_email')) {
            $login_status = $usersRepository->findOneBy(["id"=>$session->get('user_id'), "email"=>$session->get('user_email')]);
            if($login_status == null) return $this->redirectToRoute("home");
        }
        else return $this->redirectToRoute("home");

        // poll style
        return $this->render(__FUNCTION__.".html.twig");
    }


    /**
     * @Route("/user/homepage/poll/create", name="poll_create", methods={"GET", "POST"})
     */
    public function pollCreate(PollsRepository $pollsRepository, Session $session, Request $request, UsersRepository $usersRepository) : Response {
        // prevent accessing the route without priviledge protection

        // add a basic pool
        $title = $request->get('title');
        $yesVotes = 0;
        $noVotes  = 0;
        $status   = 1;
        $author = $session->get("user_id");
        $user = $usersRepository->findOneBy(["id"=>$author]);


        // check if poll already exists
        $poll_duplicates = $pollsRepository->findOneBy(array("title" => $title));
        if ($poll_duplicates == null) {
            $newPoll = new Polls();
            $newPoll->setTitle($title);
            $newPoll->setNoVote($noVotes);
            $newPoll->setYesVote($yesVotes);
            $newPoll->setUser($user);
            $newPoll->setStatus($status);

            // add to database
            $this->entityManager->persist($newPoll);
            $this->entityManager->flush();
        }
        else return new Response("This poll already exists !");
        return new Response("Poll ".$title." has been created!");
    }



    /**
     * @Route("/user/homepage/poll/delete", name="poll_delete", methods={"GET", "POST"})
     */
    public function pollDelete(Request $request, PollsRepository $pollsRepository) {
        // prevent accessing the route without priviledge protection

        // trag numele articolului pe care vreau sa l sterg
        $toDelete = $request->get("target");

        // selectez row-ul de sters in variabila now
        $now = $pollsRepository->findOneBy(["title" => $toDelete]);

        // sterg
        if ($now != null) {
            $this->entityManager->remove($now);
            $this->entityManager->flush();
        } else return new Response("Nothing to be done");
        return new Response("done");
    }



    /**
     * @Route("/user/homepage/poll/vote/{vote}/{poll}", name="poll_vote", methods={"GET", "POST"})
     */
    public function vote(Session $session, Request $request, $vote, UsersRepository $usersRepository, PollsService $pollsService, PollsRepository $pollsRepository, VotesRepository $votesRepository, $poll, VotesService $voteService) : Response {
        // prevent accessing the route without priviledge protection


            // pull data from post request
            $poll_id = $poll; // poll id
            $user_id = $session->get("user_id");  // actor of vote

            // perform voting
            try {
                $pollsService->vote($this->entityManager, $vote, $poll_id, $user_id, $pollsRepository, $votesRepository, $usersRepository, $voteService);
            }
            catch (\Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            return $this->redirectToRoute("pollView");
            //return new Response("Clicked");

    }



    /**
     * @Route("/user/homepage/poll/display", name="pollView", methods={"GET", "POST"})
     */
    public function display(VotesService $votesService, VotesRepository $votesRepository, PollsRepository $pollsRepository, Session $session, UsersRepository $usersRepository) {
        // prevent accessing the route without priviledge protection

        // get polls from database with status active
        $polls = $pollsRepository->findBy(["status" => 1]);
        $result = array();
        foreach ($polls as $poll) {
          $now = array();
          $now["id"] =  $poll->getId();
          $now["title"] = $poll->getTitle();
          $now["yes"] = $poll->getYesVote();
          $now["no"] = $poll->getNoVote();
          // 1-> didn t voted           2-> vote yes           3->vote no
          if ($votesRepository->findOneBy(["polls" => $poll->getId(), "users" => $session->get("user_id")]) == null) {
              // inca nu a votat
              $now["voted"] = 1;
          }
          else {
              $vot = $votesRepository->findOneBy(["users" => $session->get("user_id"), "polls" => $poll->getId()]);
              if($vot->getVote() == 0) $now["voted"] = 3;
              else $now["voted"] = 2;
              /* AUR
              if($user){
                  dd($user->getVotes()->getValues());
              }
              */
          }
          array_push($result, $now);

        }
        $options = array('polls'=>$result);
        return $this->render(__FUNCTION__.".html.twig",$options);
        // structure vote_name, vote_results, if you can vote add buttons else display vote

    }
}