<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230202170326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES
                ('e0d93630-acf7-4722-81e8-ff7d5fa64b66', 'DiaLog'),
                ('5e82d06d-93c4-4b1c-8c7f-557dceb2ca52', 'Mairie de Savenay')
        ");

        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'999b41a8-b48a-4487-81e6-7285f464c15b\', \'Mathieu MARCHOIS\', \'mathieu.marchois@beta.gouv.fr\', \'$2y$13$X8iaV02QuXVNAt5Pg0a75u1OoBMMef2uM2crA8UfnEYWDeviuXcwm\'),
                (\'7115057a-30ad-4ed9-9c3e-42372834afee\', \'Florimond MANCA\', \'florimond.manca@beta.gouv.fr\', \'$2y$13$d7IEnFWKIIMonUOEKSlJhe3AonEVPv2mAq.AnGmeWqFkvSbGNnsJK\'),
                (\'6586e9e8-dcba-4298-90f4-e557d5222100\', \'Mathieu FERNANDEZ\', \'mathieu.fernandez@beta.gouv.fr\', \'$2y$13$2oBXQMVgGXVyCcXPk.XHG.mKfyiM2br5Esu3DUc/fE/q4FR4gPRVe\'),
                (\'066b7bea-81d7-433a-b245-e540fda00693\', \'Aurélie BATON\', \'aurelie.baton@beta.gouv.fr\', \'$2y$13$K2XPGVl7g3D9aQktAGF4Hec/1EBt2KUHei7ABBFlWee7vnocAniGK\'),
                (\'9f693c3c-6ed9-4ee5-b725-3d193e29ddd3\', \'Anne-Sophie TRANCHET\', \'anne-sophie.tranchet@beta.gouv.fr\', \'$2y$13$aDt.YQ/XxdYP/H4RuTWNguVG8Yq7PSsInGdiqFsq2uQyBCZzoQpQu\'),
                (\'459d065a-707d-4690-a55a-b2f17c904fbb\', \'Christelle ERRAUD\', \'c.erraud@ville-savenay.fr\', \'$2y$13$5HobXwM3FNYFgJRTxY8fqOjFFFseHihsOAB1GWMT/Vh.PhRWNn5ZW\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('999b41a8-b48a-4487-81e6-7285f464c15b', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('7115057a-30ad-4ed9-9c3e-42372834afee', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('6586e9e8-dcba-4298-90f4-e557d5222100', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('066b7bea-81d7-433a-b245-e540fda00693', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('9f693c3c-6ed9-4ee5-b725-3d193e29ddd3', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'),
                ('459d065a-707d-4690-a55a-b2f17c904fbb', '5e82d06d-93c4-4b1c-8c7f-557dceb2ca52')
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
