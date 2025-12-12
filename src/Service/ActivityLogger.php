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

    public function log(string $action, ?string $targetData = null, $overrideUser = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $overrideUser ?? $this->security->getUser();

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setIpAddress($request?->getClientIp());

        if ($user) {
            // ManyToOne link
            $log->setUser($user);

            // Username display (works even if your entity doesn't have getUsername())
            $identifier = method_exists($user, 'getUserIdentifier')
                ? $user->getUserIdentifier()
                : (method_exists($user, 'getUsername') ? $user->getUsername() : null);

            $log->setUsername($identifier);

            // Pick best role: ADMIN > STAFF > USER (or just join them)
            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
            $log->setRole(implode(',', $roles));
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}
