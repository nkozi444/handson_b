<?php

namespace App\Controller;

use App\Entity\Tour;
use App\Form\TourType;
use App\Repository\TourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function dashboard(TourRepository $tourRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        // ✅ Option A: fetch tours that belong to this user
        $tours = $tourRepository->findBy(
            ['user' => $user],
            ['date' => 'DESC']
        );

        $now = new \DateTimeImmutable();
        $upcomingTours = array_filter($tours, fn(Tour $t) => $t->getDate() && $t->getDate() > $now);
        $pastTours     = array_filter($tours, fn(Tour $t) => $t->getDate() && $t->getDate() <= $now);

        return $this->render('user/dashboard.html.twig', [
            'tours'         => $tours,
            'upcomingCount' => count($upcomingTours),
            'pastCount'     => count($pastTours),
            'user'          => $user,
        ]);
    }

    #[Route('/user/book-tour', name: 'app_user_book_tour')]
    public function bookTour(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $tour = new Tour();

        // ✅ Link tour to logged-in user
        $tour->setUser($user);

        // Optional: store identifier in email column (keep if you rely on it in UI)
        $tour->setEmail($user->getUserIdentifier());

        $form = $this->createForm(TourType::class, $tour, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (method_exists($tour, 'getRequestedExhibition') && $tour->getRequestedExhibition()) {
                $tour->setExhibition(null);
            }

            $this->entityManager->persist($tour);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tour booked successfully!');
            return $this->redirectToRoute('app_user_bookings');
        }

        return $this->render('user/book_tour.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/bookings', name: 'app_user_bookings')]
    public function viewBookings(TourRepository $tourRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $bookings = $tourRepository->findBy(
            ['user' => $user],
            ['date' => 'DESC']
        );

        return $this->render('user/bookings.html.twig', [
            'bookings' => $bookings,
            'user'     => $user,
        ]);
    }

    // ✅ VIEW + EDIT booking (User can edit, but cannot cancel/confirm)
    #[Route('/user/booking/{id}', name: 'app_user_booking_view', methods: ['GET', 'POST'])]
    public function viewBooking(Tour $tour, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // ✅ Ownership check
        if ($tour->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this booking.');
        }

        // Create edit form
        $form = $this->createForm(TourType::class, $tour, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // If user typed a custom exhibition request, clear dropdown selection
            if (method_exists($tour, 'getRequestedExhibition') && $tour->getRequestedExhibition()) {
                $tour->setExhibition(null);
            }

            // 🔒 Safety: user cannot change status even if they try to tamper
            // (TourType has status disabled, but we enforce anyway)
            // Do nothing to status here; it remains whatever it was.

            $this->entityManager->flush();

            $this->addFlash('success', 'Booking updated successfully.');
            return $this->redirectToRoute('app_user_booking_view', ['id' => $tour->getId()]);
        }

        return $this->render('user/view_booking.html.twig', [
            'booking' => $tour,
            'form'    => $form->createView(),
        ]);
    }

    // ✅ JSON endpoint for polling (real-time updates)
    #[Route('/user/bookings.json', name: 'app_user_bookings_json', methods: ['GET'])]
    public function bookingsJson(TourRepository $tourRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $bookings = $tourRepository->findBy(['user' => $user], ['date' => 'DESC']);

        $payload = array_map(function (Tour $t) {
            return [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'email' => $t->getEmail(),
                'date' => $t->getDate()?->format('Y-m-d'),
                'dateTime' => $t->getDate()?->format('Y-m-d H:i'),
                'numberOfGuests' => $t->getNumberOfGuests(),
                'status' => $t->getStatus(),
                'exhibitionTitle' => $t->getExhibition()?->getTitle(),
                'requestedExhibition' => $t->getRequestedExhibition(),
                'updatedAt' => $t->getUpdatedAt()?->format('c'),
            ];
        }, $bookings);

        return $this->json([
            'bookings' => $payload,
            'serverTime' => (new \DateTimeImmutable())->format('c'),
        ]);
    }
}
