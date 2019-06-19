<?php

namespace App\Controller;

use App\Entity\Polls;
use App\Entity\Users;
use App\Repository\PollsRepository;
use App\Repository\UsersRepository;
use App\Service\PollsService;
use App\Service\UsersService;
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
     * @Route("/user/add", name="users_add", methods={"GET", "POST"})
     */
    public function add(Request $request, UsersService $usersService): Response
    {

        $adminStatus = true;

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

        try {
            $this->entityManager->persist($newUser);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage());
        }

        //return new Response($request->getClientIp());
        return $this->render(__FUNCTION__ . ".html.twig");
    }

    /**
     *
     * @Route("/user/admins/list", name="users_admins_list", methods={"GET"})
     */
    public function listAdmins(UsersRepository $usersRepository, Session $session, Request $request): Response
    {
        $adminArray = $usersRepository->findBy(["isAdmin" => true]);
        // daca nu gaseste nimic returneazza array gol
        // if (count($adminArray) == 0 ) gol

        $user = $usersRepository->findOneBy(["email" => $request->get("email"), "password" => sha1($request->get("password"))]);
        //        // daca nu gaseste nimic returneazza null
        // if ($user == null ) gol
/*
        //functie logare
        $session->set('user_id', $user->getId());  // in loc de 5 am id lui din db dupa match mail

        //in fiecare ruta cu drepturi de acces
        if($session->get('user_id')) {
            $usersRepository->find($session->get('user_id'));
        } $this->>redirectToRoute("nume_ruta");
*/
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
    public function pollCreate(PollsRepository $pollsRepository, Request $request) : Response {
        // add a basic pool
        $title = $request->get('title');
        $yesVotes = 0;
        $noVotes  = 0;
        $status   = true;
        $author = $request->get("user_email");

        // check if poll already exists
        $poll_duplicates = $pollsRepository->findOneBy(array("title" => $title));
        if ($poll_duplicates == null) {
            $newPoll = new Polls();
            $newPoll->setTitle($title);
            $newPoll->setNoVote($noVotes);
            $newPoll->setYesVote($yesVotes);
            $newPoll->setAuthor($author);
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
        // trag numele articolului pe care vreu sa l sterg
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


}