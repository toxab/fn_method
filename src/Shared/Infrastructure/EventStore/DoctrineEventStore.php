<?php

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\Event\DomainEventInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class DoctrineEventStore implements EventStoreInterface
{
    private const TABLE_NAME = 'event_store';

    public function __construct(
        private Connection $connection
    ) {}

    public function saveEvents(string $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->beginTransaction();
        
        try {
            $currentVersion = $this->getAggregateVersion($aggregateId);
            
            if ($currentVersion !== $expectedVersion) {
                throw new \RuntimeException(
                    "Concurrency conflict: expected version {$expectedVersion}, got {$currentVersion}"
                );
            }
            
            foreach ($events as $event) {
                $this->saveEvent($aggregateId, $event, ++$currentVersion);
            }
            
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getEventsForAggregate(string $aggregateId): array
    {
        return $this->getEventsForAggregateFromVersion($aggregateId, 0);
    }

    public function getEventsForAggregateFromVersion(string $aggregateId, int $version): array
    {
        $sql = "
            SELECT event_type, event_data, version, occurred_at
            FROM " . self::TABLE_NAME . "
            WHERE aggregate_id = :aggregate_id AND version > :version
            ORDER BY version ASC
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':aggregate_id', $aggregateId);
        $stmt->bindValue(':version', $version);
        
        $result = $stmt->executeQuery();
        $events = [];
        
        foreach ($result->fetchAllAssociative() as $row) {
            $events[] = $this->deserializeEvent($row);
        }
        
        return $events;
    }

    public function getAllEvents(): array
    {
        $sql = "
            SELECT aggregate_id, event_type, event_data, version, occurred_at
            FROM " . self::TABLE_NAME . "
            ORDER BY id ASC
        ";
        
        $result = $this->connection->executeQuery($sql);
        $events = [];
        
        foreach ($result->fetchAllAssociative() as $row) {
            $events[] = $this->deserializeEvent($row);
        }
        
        return $events;
    }

    public function getEventsByType(string $eventType): array
    {
        $sql = "
            SELECT aggregate_id, event_type, event_data, version, occurred_at
            FROM " . self::TABLE_NAME . "
            WHERE event_type = :event_type
            ORDER BY id ASC
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':event_type', $eventType);
        
        $result = $stmt->executeQuery();
        $events = [];
        
        foreach ($result->fetchAllAssociative() as $row) {
            $events[] = $this->deserializeEvent($row);
        }
        
        return $events;
    }

    private function saveEvent(string $aggregateId, DomainEventInterface $event, int $version): void
    {
        $sql = "
            INSERT INTO " . self::TABLE_NAME . "
            (aggregate_id, event_type, event_data, version, occurred_at)
            VALUES (:aggregate_id, :event_type, :event_data, :version, :occurred_at)
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':aggregate_id', $aggregateId);
        $stmt->bindValue(':event_type', $event->getEventType());
        $stmt->bindValue(':event_data', json_encode($event->getEventData()));
        $stmt->bindValue(':version', $version);
        $stmt->bindValue(':occurred_at', $event->getOccurredAt()->format('Y-m-d H:i:s'));
        
        try {
            $stmt->executeStatement();
        } catch (UniqueConstraintViolationException $e) {
            throw new \RuntimeException('Concurrency conflict detected', 0, $e);
        }
    }

    private function getAggregateVersion(string $aggregateId): int
    {
        $sql = "
            SELECT MAX(version) as max_version
            FROM " . self::TABLE_NAME . "
            WHERE aggregate_id = :aggregate_id
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':aggregate_id', $aggregateId);
        
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();
        
        return $row['max_version'] ?? 0;
    }

    private function deserializeEvent(array $row): DomainEventInterface
    {
        $eventType = $row['event_type'];
        $eventData = json_decode($row['event_data'], true);
        
        if (!class_exists($eventType)) {
            throw new \RuntimeException("Event type {$eventType} not found");
        }
        
        // Use reflection to create instance with proper constructor arguments
        $reflectionClass = new \ReflectionClass($eventType);
        $constructor = $reflectionClass->getConstructor();
        
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $args = [];
            
            foreach ($parameters as $param) {
                $paramName = $param->getName();
                
                if ($paramName === 'currency') {
                    $args[] = \App\Account\Domain\ValueObject\Currency::from($eventData[$paramName]);
                } elseif ($paramName === 'role') {
                    $args[] = \App\User\Domain\ValueObject\UserRole::from($eventData[$paramName]);
                } elseif ($paramName === 'amount') {
                    $args[] = new \App\Account\Domain\ValueObject\Money(
                        $eventData['amount'],
                        \App\Account\Domain\ValueObject\Currency::from($eventData['currency'])
                    );
                } elseif ($paramName === 'oldEmail' || $paramName === 'newEmail') {
                    // Deserialization of Email VO for UserEmailChangedEvent
                    $args[] = new \App\User\Domain\ValueObject\Email($eventData[$paramName]);
                } else {
                    $args[] = $eventData[$paramName] ?? null;
                }
            }
            
            return $reflectionClass->newInstanceArgs($args);
        }
        
        return new $eventType();
    }
}
