<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250317143728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signing_authority set road_name=\'20, rue du Lac\', city_code=\'69003\' , city_label=\'Lyon\' where uuid=\'01939c6b-a7c2-7a95-953c-f262bd416880\'');
        $this->addSql('UPDATE signing_authority set road_name=\'5, rue Jean Lissar\', city_code=\'64240\' , city_label=\'Hasparren\' where uuid=\'0193d94d-c43d-73ad-a052-33eedd8d07ed\'');
        $this->addSql('UPDATE signing_authority set road_name=\'9 place de la Mairie\', city_code=\'67240\' , city_label=\'BISCHWILLER\' where uuid=\'0193df1a-e50d-781a-8cc1-b314e6fb793c\'');
        $this->addSql('UPDATE signing_authority set road_name=\'110 rue de l\'\'Hôtel de Ville\', city_code=\'30190\' , city_label=\'Sainte Anastasie\' where uuid=\'019445c7-33e0-77e0-905f-b130d6403ada\'');
        $this->addSql('UPDATE signing_authority set road_name=\'rue Rouget de Lisle\', city_code=\'62580\' , city_label=\'VIMY\' where uuid=\'01947393-0db0-7f45-964f-007873fab548\'');
        $this->addSql('UPDATE signing_authority set road_name=\'9 rue Louise Michel\', city_code=\'09000\' , city_label=\'Foix\' where uuid=\'019491d4-0d7d-7e26-917d-b0d207ef0f59\'');
        $this->addSql('UPDATE signing_authority set road_name=\'19 rue Saint Martin\', city_code=\'27830\' , city_label=\'Neaufles-Saint-Martin\' where uuid=\'019492b7-2900-788a-b859-430e09639c1f\'');
        $this->addSql('UPDATE signing_authority set road_name=\'01 place de la République\', city_code=\'59260\' , city_label=\'LEZENNES\' where uuid=\'01949349-9055-75c6-a237-f00fdbb65c1b\'');
        $this->addSql('UPDATE signing_authority set road_name=\'Place d\'\'Armes\', city_code=\'57370\' , city_label=\'Phalsbourg\' where uuid=\'0194bd38-2eff-72d5-8063-ea368203efda\'');
        $this->addSql('UPDATE signing_authority set road_name=\'108, avenue Maurice Maunoury\', city_code=\'28600\' , city_label=\'Luisant\' where uuid=\'0194d0ef-9ce7-7794-b329-3609671233fe\'');
        $this->addSql('UPDATE signing_authority set road_name=\'ROUTE DE FRILEUSE\', city_code=\'27150\' , city_label=\'NOJEON-EN-VEXIN\' where uuid=\'01953dcb-0979-7ab7-9c51-4393e1651456\'');
    }

    public function down(Schema $schema): void
    {
    }
}
