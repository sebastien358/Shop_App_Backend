<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014194927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE command (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(125) NOT NULL, last_name VARCHAR(125) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(50) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, phone_number VARCHAR(50) NOT NULL, INDEX IDX_8ECAEAD4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE command_items (id INT AUTO_INCREMENT NOT NULL, command_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_72B6BC7E33E1689A (command_id), INDEX IDX_72B6BC7E4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE command ADD CONSTRAINT FK_8ECAEAD4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE command_items ADD CONSTRAINT FK_72B6BC7E33E1689A FOREIGN KEY (command_id) REFERENCES command (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE command_items ADD CONSTRAINT FK_72B6BC7E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE command DROP FOREIGN KEY FK_8ECAEAD4A76ED395');
        $this->addSql('ALTER TABLE command_items DROP FOREIGN KEY FK_72B6BC7E33E1689A');
        $this->addSql('ALTER TABLE command_items DROP FOREIGN KEY FK_72B6BC7E4584665A');
        $this->addSql('DROP TABLE command');
        $this->addSql('DROP TABLE command_items');
    }
}
