<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216142544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model DROP CONSTRAINT fk_d79572d93256915b');
        $this->addSql('DROP INDEX idx_d79572d93256915b');
        $this->addSql('ALTER TABLE model RENAME COLUMN relation_id TO brand_id');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D944F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D79572D944F5D008 ON model (brand_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model DROP CONSTRAINT FK_D79572D944F5D008');
        $this->addSql('DROP INDEX IDX_D79572D944F5D008');
        $this->addSql('ALTER TABLE model RENAME COLUMN brand_id TO relation_id');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT fk_d79572d93256915b FOREIGN KEY (relation_id) REFERENCES brand (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d79572d93256915b ON model (relation_id)');
    }
}
