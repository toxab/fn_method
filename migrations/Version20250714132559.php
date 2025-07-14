<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714132559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_store table for Event Sourcing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_store (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            aggregate_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            event_data JSON NOT NULL,
            version INT NOT NULL,
            occurred_at DATETIME NOT NULL,
            INDEX idx_aggregate_id (aggregate_id),
            INDEX idx_event_type (event_type),
            INDEX idx_occurred_at (occurred_at),
            UNIQUE KEY unique_aggregate_version (aggregate_id, version)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_store');
    }
}
