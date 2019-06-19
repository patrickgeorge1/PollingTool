<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\UsersService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
                $newUser->setPassword(password_hash($request->get("password"), PASSWORD_BCRYPT));
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

        $user = $usersRepository->findOneBy(["email" => $request->get("email"), "password" => password_hash($request->get("password"), PASSWORD_BCRYPT)]);
        // daca nu gaseste nimic returneazza null
        // if ($user == null ) gol
/*
        //functie logare
        $session->set('user_id', $user->getId());  // in loc de 5 am id lui din db dupa match mail

        //in fiecare ruta cu drepturi de acces
        if($session->get('user_id')) {
            $usersRepository->find($session->get('user_id'));
        }
*/
        return $this->render(__FUNCTION__ . '.html.twig', [
            'admins' => $adminArray
        ]);

    }


}