<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param mixed $overrideUser If you want to log as another user (optional).
     * @param bool  $flush        IMPORTANT: set false when called during Doctrine flush events.
     */
    public function log(
        string $action,
        ?string $targetData = null,
        $overrideUser = null,
        bool $flush = true
    ): ActivityLog {
        $request = $this->requestStack->getCurrentRequest();
        $user = $overrideUser ?? $this->security->getUser();

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setIpAddress($request?->getClientIp());

        if ($user) {
            $log->setUser($user);

            $identifier = method_exists($user, 'getUserIdentifier')
                ? $user->getUserIdentifier()
                : (method_exists($user, 'getUsername') ? $user->getUsername() : null);

            $log->setUsername($identifier);

            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
            $log->setRole(implode(',', $roles));
        }

        $this->em->persist($log);

        if ($flush) {
            $this->em->flush();
        }

        return $log;
    }
}
