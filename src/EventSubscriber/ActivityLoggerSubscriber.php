<?php

namespace App\EventSubscriber;

use App\Entity\log\ActivityLog;
use App\Entity\project\Project;
use App\Entity\task\Task;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::onFlush)]
class ActivityLoggerSubscriber
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * The onFlush event is fired right before Doctrine commits entities to the database.
     * This makes it the perfect place to safely hook in, read the "UnitOfWork" (the changes pending),
     * and log them into the ActivityLog table in the same transaction.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork(); // Gets all pending Inserts/Updates/Deletes

        // 1. Check Insertions
        // Loop over the entities scheduled to be created.
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            // We only track specific entities (like Project and Task) to prevent recursive logging
            if ($this->isTrackable($entity) && !$entity instanceof ActivityLog) {
                // Determine if we have changeset. Insertions usually have empty previous state.
                $this->logActivity('CREATE', $entity, $em, $uow->getEntityChangeSet($entity));
            }
        }

        // 2. Check Updates
        // Loop over entities scheduled to be updated.
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isTrackable($entity)) {
                // getEntityChangeSet returns an array like: ['fieldName' => [oldValue, newValue]]
                $changeSet = $uow->getEntityChangeSet($entity);
                $filteredChanges = [];
                
                // We format the values (e.g. converting DateTimes to string) so JSON encodes cleanly.
                foreach ($changeSet as $field => $values) {
                    $old = $this->formatValue($values[0] ?? null);
                    $new = $this->formatValue($values[1] ?? null);
                    
                    // Only log the field if it actually changed meaningfully
                    if ($old !== $new) {
                        $filteredChanges[$field] = [$old, $new];
                    }
                }
                
                // If it's a Task status becoming DONE, maybe action should be COMPLETED, etc.
                // But tracking as UPDATE is fine.
                if (!empty($filteredChanges)) {
                    $this->logActivity('UPDATE', $entity, $em, $filteredChanges);
                }
            }
        }

        // 3. Check Deletions
        // Loop over entities scheduled for deletion
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->isTrackable($entity)) {
                $this->logActivity('DELETE', $entity, $em);
            }
        }
    }

    private function isTrackable(object $entity): bool
    {
        return $entity instanceof Task || $entity instanceof Project;
    }

    private function formatValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_object($value)) {
            if (method_exists($value, 'getIdTask')) return $value->getIdTask();
            if (method_exists($value, 'getIdProject')) return $value->getIdProject();
            if (method_exists($value, 'getId')) return $value->getId();
            
            if ($value instanceof \UnitEnum) {
                return $value->value ?? $value->name;
            }

            return get_class($value);
        }
        
        return $value;
    }

    private function logActivity(string $action, object $entity, $em, ?array $changes = null): void
    {
        $session = $this->requestStack->getSession();
        $userId = $session ? $session->get('user_id') : null;

        $log = new ActivityLog();
        $log->setUserId($userId ? (int) $userId : null);
        $log->setActionType($action);
        
        $className = (new \ReflectionClass($entity))->getShortName();
        $log->setEntityName($className);
        
        // At onFlush for CREATE, the ID might not exist yet if it's auto-increment!
        // We capture to 0 for CREATE if ID isn't assigned. 
        if (method_exists($entity, 'getIdTask')) {
            $log->setEntityId($entity->getIdTask() ?? 0);
        } elseif (method_exists($entity, 'getIdProject')) {
            $log->setEntityId($entity->getIdProject() ?? 0);
        } elseif (method_exists($entity, 'getId')) {
            $log->setEntityId($entity->getId() ?? 0);
        } else {
            $log->setEntityId(0);
        }

        if ($changes !== null) {
            $log->setChanges($changes);
        }

        $em->persist($log);
        
        // This is strictly required for onFlush inserts wrapper
        $uow = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(ActivityLog::class);
        $uow->computeChangeSet($classMetadata, $log);
    }
}
