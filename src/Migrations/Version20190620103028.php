<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190620103028 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE polls ADD CONSTRAINT FK_1D3CC6EEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_1D3CC6EEA76ED395 ON polls (user_id)');
        $this->addSql('ALTER TABLE votes ADD polls_id INT NOT NULL, ADD users_id INT NOT NULL');
        $this->addSql('ALTER TABLE votes ADD CONSTRAINT FK_518B7ACF77F234C8 FOREIGN KEY (polls_id) REFERENCES polls (id)');
        $this->addSql('ALTER TABLE votes ADD CONSTRAINT FK_518B7ACF67B3B43D FOREIGN KEY (users_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_518B7ACF77F234C8 ON votes (polls_id)');
        $this->addSql('CREATE INDEX IDX_518B7ACF67B3B43D ON votes (users_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE polls DROP FOREIGN KEY FK_1D3CC6EEA76ED395');
        $this->addSql('DROP INDEX IDX_1D3CC6EEA76ED395 ON polls');
        $this->addSql('ALTER TABLE votes DROP FOREIGN KEY FK_518B7ACF77F234C8');
        $this->addSql('ALTER TABLE votes DROP FOREIGN KEY FK_518B7ACF67B3B43D');
        $this->addSql('DROP INDEX IDX_518B7ACF77F234C8 ON votes');
        $this->addSql('DROP INDEX IDX_518B7ACF67B3B43D ON votes');
        $this->addSql('ALTER TABLE votes DROP polls_id, DROP users_id');
    }
}
