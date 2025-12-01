<?php

namespace App\Controller;

use App\Entity\Tour;
use App\Form\TourType;
use App\Repository\TourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tour')]
final class TourController extends AbstractController
{
    /**
     * Dashboard (4 metrics, no charts)
     */
    #[Route('/dashboard', name: 'app_tour_dashboard', methods: ['GET'])]
    public function dashboard(TourRepository $tourRepository): Response
    {
        $now = new \DateTimeImmutable('now');

        // Total tours
        $totalTours = $tourRepository->count([]);

        // Upcoming (today or later)
        $upcomingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.date IS NOT NULL')
            ->andWhere('t.date >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // Pending
        $pendingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        // Completed (status = confirmed)
        $completedTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        // Recent tours table
        $recentTours = $tourRepository->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(7)
            ->getQuery()
            ->getResult();

        return $this->render('tour/dashboard_index.html.twig', [
            'totalTours'    => $totalTours,
            'upcomingTours' => $upcomingTours,
            'pendingTours'  => $pendingTours,
            'completedTours'=> $completedTours,
            'recentTours'   => $recentTours,
        ]);
    }

    /**
     * List + search tours
     */
    #[Route('/', name: 'app_tour_index', methods: ['GET'])]
    public function index(Request $request, TourRepository $tourRepository): Response
    {
        $q     = trim((string) $request->query->get('q', ''));
        $tours = $tourRepository->searchWithExhibition($q);

        return $this->render('tour/index.html.twig', [
            'tours' => $tours,
            'q'     => $q,
        ]);
    }

    /**
     * Create new tour
     */
    #[Route('/new', name: 'app_tour_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tour = new Tour();
        $form = $this->createForm(TourType::class, $tour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tour);
            $entityManager->flush();

            $this->addFlash('success', 'Tour created successfully.');

            return $this->redirectToRoute('app_tour_index');
        }

        return $this->render('tour/new.html.twig', [
            'tour' => $tour,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show tour
     */
    #[Route('/{id}', name: 'app_tour_show', methods: ['GET'])]
    public function show(Tour $tour): Response
    {
        return $this->render('tour/show.html.twig', [
            'tour' => $tour,
        ]);
    }

    /**
     * Edit tour
     */
    #[Route('/{id}/edit', name: 'app_tour_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tour $tour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TourType::class, $tour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Tour updated successfully.');

            return $this->redirectToRoute('app_tour_index');
        }

        return $this->render('tour/edit.html.twig', [
            'tour' => $tour,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete tour
     */
    #[Route('/{id}', name: 'app_tour_delete', methods: ['POST'])]
    public function delete(Request $request, Tour $tour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tour->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tour);
            $entityManager->flush();
            $this->addFlash('success', 'Tour deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_tour_index');
    }
}
