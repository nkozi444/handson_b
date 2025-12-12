<?php

namespace App\Controller;

use App\Entity\Tour;
use App\Form\TourType;
use App\Repository\TourRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tour')]
final class TourController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Dashboard (4 metrics, no charts)
     */
    #[Route('/dashboard', name: 'app_tour_dashboard', methods: ['GET'])]
    public function dashboard(TourRepository $tourRepository): Response
    {
        $now = new \DateTimeImmutable('now');

        $totalTours = $tourRepository->count([]);

        $upcomingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.date IS NOT NULL')
            ->andWhere('t.date >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        $pendingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $completedTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        $recentTours = $tourRepository->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(7)
            ->getQuery()
            ->getResult();

        return $this->render('tour/dashboard_index.html.twig', [
            'totalTours'     => $totalTours,
            'upcomingTours'  => $upcomingTours,
            'pendingTours'   => $pendingTours,
            'completedTours' => $completedTours,
            'recentTours'    => $recentTours,
        ]);
    }

    /**
     * ✅ JSON ENDPOINT FOR POLLING: DASHBOARD STATS + RECENT TOURS
     */
    #[Route('/dashboard-snapshot.json', name: 'app_tour_dashboard_snapshot_json', methods: ['GET'])]
    public function dashboardSnapshotJson(TourRepository $tourRepository): JsonResponse
    {
        $now = new \DateTimeImmutable('now');

        $totalTours = $tourRepository->count([]);

        $upcomingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.date IS NOT NULL')
            ->andWhere('t.date >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        $pendingTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $completedTours = (int) $tourRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('LOWER(t.status) = :status')
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        $recentTours = $tourRepository->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(7)
            ->getQuery()
            ->getResult();

        $recent = array_map(function (Tour $t) {
            $status = strtolower($t->getStatus() ?? '');
            $statusClass = $status === 'confirmed' ? 'confirmed' : ($status === 'pending' ? 'pending' : 'other');

            return [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'email' => $t->getEmail(),
                'date' => $t->getDate()?->format('Y-m-d H:i'),
                'status' => $t->getStatus(),
                'statusClass' => $statusClass,
                'updatedAt' => $t->getUpdatedAt()?->format('c'),
            ];
        }, $recentTours);

        return $this->json([
            'stats' => [
                'totalTours' => $totalTours,
                'upcomingTours' => $upcomingTours,
                'pendingTours' => $pendingTours,
                'completedTours' => $completedTours,
            ],
            'recentTours' => $recent,
            'serverTime' => $now->format('c'),
        ]);
    }

    /**
     * List + search tours
     */
    #[Route('/', name: 'app_tour_index', methods: ['GET'])]
    public function index(Request $request, TourRepository $tourRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $tours = $tourRepository->searchWithExhibition($q);

        return $this->render('tour/index.html.twig', [
            'tours' => $tours,
            'q'     => $q,
        ]);
    }

    /**
     * Create new tour (Admin)
     */
    #[Route('/new', name: 'app_tour_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tour = new Tour();
        $form = $this->createForm(TourType::class, $tour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (method_exists($tour, 'getRequestedExhibition') && $tour->getRequestedExhibition()) {
                $tour->setExhibition(null);
            }

            $entityManager->persist($tour);
            $entityManager->flush();

            $this->activityLogger->log(
                'CREATE',
                'Tour#' . $tour->getId() . ' (' . $tour->getName() . ') ' . $tour->getDate()->format('Y-m-d H:i'),
                $this->getUser()
            );

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
     * Update tour status (Admin only)
     */
    #[Route('/{id}/update-status', name: 'app_tour_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Tour $tour, EntityManagerInterface $entityManager): RedirectResponse
    {
        $status = (string) $request->request->get('status');

        if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $tour->setStatus($status);
            $entityManager->flush();
        }

        $this->activityLogger->log(
            'UPDATE',
            'Tour#' . $tour->getId() . ' Status Update: ' . $status,
            $this->getUser()
        );

        $this->addFlash('success', 'Tour status updated successfully!');

        return $this->redirectToRoute('app_tour_show', ['id' => $tour->getId()]);
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

            if (method_exists($tour, 'getRequestedExhibition') && $tour->getRequestedExhibition()) {
                $tour->setExhibition(null);
            }

            $entityManager->flush();

            $this->activityLogger->log(
                'UPDATE',
                'Tour#' . $tour->getId() . ' (' . $tour->getName() . ') updated.',
                $this->getUser()
            );

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
        if ($this->isCsrfTokenValid('delete' . $tour->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($tour);
            $entityManager->flush();

            $this->activityLogger->log(
                'DELETE',
                'Tour#' . $tour->getId() . ' (' . $tour->getName() . ') deleted.',
                $this->getUser()
            );

            $this->addFlash('success', 'Tour deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_tour_index');
    }
}
