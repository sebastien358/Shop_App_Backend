<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013163314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_items ADD product_id INT NOT NULL');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484454584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BEF484454584665A ON cart_items (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF484454584665A');
        $this->addSql('DROP INDEX IDX_BEF484454584665A ON cart_items');
        $this->addSql('ALTER TABLE cart_items DROP product_id');
    }
}
