<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423131240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE busy_appointments ADD ground_id INT NOT NULL, ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE busy_appointments ADD CONSTRAINT FK_A77FF2FA1D297B0A FOREIGN KEY (ground_id) REFERENCES tennis_ground (id)');
        $this->addSql('ALTER TABLE busy_appointments ADD CONSTRAINT FK_A77FF2FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_A77FF2FA1D297B0A ON busy_appointments (ground_id)');
        $this->addSql('CREATE INDEX IDX_A77FF2FAA76ED395 ON busy_appointments (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE busy_appointments DROP FOREIGN KEY FK_A77FF2FA1D297B0A');
        $this->addSql('ALTER TABLE busy_appointments DROP FOREIGN KEY FK_A77FF2FAA76ED395');
        $this->addSql('DROP INDEX IDX_A77FF2FA1D297B0A ON busy_appointments');
        $this->addSql('DROP INDEX IDX_A77FF2FAA76ED395 ON busy_appointments');
        $this->addSql('ALTER TABLE busy_appointments DROP ground_id, DROP user_id');
    }
}
