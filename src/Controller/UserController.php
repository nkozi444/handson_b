<?php

namespace App\Controller;

use App\Repository\TourRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function dashboard(TourRepository $tourRepository): Response
    {
        $user = $this->getUser();

        // Fetch tours that belong to this user based on email
        $tours = $tourRepository->findBy(
         ['email' => $user->getUsername()],
         ['date' => 'DESC']
);

        // Split into upcoming and past tours
        $upcomingTours = array_filter($tours, fn($t) => $t->getDate() > new \DateTime());
        $pastTours     = array_filter($tours, fn($t) => $t->getDate() <= new \DateTime());

        return $this->render('user/dashboard.html.twig', [
            'tours'         => $tours,
            'upcomingCount' => count($upcomingTours),
            'pastCount'     => count($pastTours),
            'user'          => $user,
        ]);
    }
}