<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class DoctrineActivityLogSubscriber
{
    public function __construct(
        private ActivityLogger $logger
    ) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // CREATE
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->shouldSkip($entity)) {
                continue;
            }

            $target = $this->describeEntity($entity);
            $log = $this->logger->log('CREATE', $target, null, false);

            $meta = $em->getClassMetadata(ActivityLog::class);
            $uow->computeChangeSet($meta, $log);
        }

        // UPDATE
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->shouldSkip($entity)) {
                continue;
            }

            $changes = $uow->getEntityChangeSet($entity);

            $target = $this->describeEntity($entity) . $this->formatChanges($changes);
            $log = $this->logger->log('UPDATE', $target, null, false);

            $meta = $em->getClassMetadata(ActivityLog::class);
            $uow->computeChangeSet($meta, $log);
        }

        // DELETE
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->shouldSkip($entity)) {
                continue;
            }

            $target = $this->describeEntity($entity);
            $log = $this->logger->log('DELETE', $target, null, false);

            $meta = $em->getClassMetadata(ActivityLog::class);
            $uow->computeChangeSet($meta, $log);
        }
    }

    private function shouldSkip(object $entity): bool
    {
        // prevent infinite recursion
        if ($entity instanceof ActivityLog) {
            return true;
        }

        // OPTIONAL: skip some entities if you want
        // if ($entity instanceof SomeEntityYouWantToIgnore) return true;

        return false;
    }

    private function describeEntity(object $entity): string
    {
        $rc = new \ReflectionClass($entity);
        $short = $rc->getShortName();

        // If entity has __toString(), use it (best readable target)
        if (method_exists($entity, '__toString')) {
            $str = trim((string) $entity);
            if ($str !== '') {
                return $short . ': ' . $str;
            }
        }

        // Try getId()
        $id = null;
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
        }

        return $short . ($id ? "#{$id}" : " (new)");
    }

    private function formatChanges(array $changes): string
    {
        // remove noisy/sensitive fields if present
        unset($changes['updatedAt'], $changes['createdAt'], $changes['password'], $changes['plainPassword']);

        if (!$changes) {
            return '';
        }

        $parts = [];
        foreach ($changes as $field => [$old, $new]) {
            $parts[] = sprintf(
                "%s: %s → %s",
                $field,
                $this->val($old),
                $this->val($new),
            );
        }

        return " | Changes: " . implode(", ", $parts);
    }

    private function val(mixed $v): string
    {
        if ($v === null) return 'null';
        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i:s');
        if (is_bool($v)) return $v ? 'true' : 'false';

        // Keep objects readable without exploding logs
        if (is_object($v)) {
            if (method_exists($v, '__toString')) {
                $s = (string) $v;
                return $this->shorten($s);
            }
            return get_class($v);
        }

        if (is_array($v)) {
            return $this->shorten(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $this->shorten((string) $v);
    }

    private function shorten(?string $s, int $max = 120): string
    {
        $s = $s ?? '';
        $s = trim($s);

        if (mb_strlen($s) <= $max) {
            return $s === '' ? '""' : $s;
        }

        return mb_substr($s, 0, $max - 3) . '...';
    }
}
