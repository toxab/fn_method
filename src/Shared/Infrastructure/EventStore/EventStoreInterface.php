<?php

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\Event\DomainEventInterface;

interface EventStoreInterface
{
    public function saveEvents(string $aggregateId, array $events, int $expectedVersion): void;
    
    public function getEventsForAggregate(string $aggregateId): array;
    
    public function getEventsForAggregateFromVersion(string $aggregateId, int $version): array;
    
    public function getAllEvents(): array;
    
    public function getEventsByType(string $eventType): array;
}