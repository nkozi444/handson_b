<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $user = $token?->getUser();

        $this->activityLogger->log(
            'LOGOUT',
            'User Logout',
            is_object($user) ? $user : null
        );
    }
}
