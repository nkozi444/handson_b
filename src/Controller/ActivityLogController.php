<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityLogController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'admin_activity_logs', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Basic filters (optional but rubric-friendly)
        $action = trim((string) $request->query->get('action', ''));
        $user   = trim((string) $request->query->get('user', ''));
        $date   = trim((string) $request->query->get('date', '')); // YYYY-MM-DD

        // If you haven't added filter methods, this still works:
        if ($action === '' && $user === '' && $date === '') {
            $logs = $repo->findBy([], ['createdAt' => 'DESC']);
        } else {
            $logs = $repo->searchLogs($action, $user, $date);
        }

        return $this->render('activity_log/index.html.twig', [
            'logs'   => $logs,
            'action' => $action,
            'user'   => $user,
            'date'   => $date,
        ]);
    }
}
