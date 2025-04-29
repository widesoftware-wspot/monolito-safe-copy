<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220127172141 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO radius_wspotv3.custom_fields_template
(identifier, name, `type`, choices, validations, mask, is_unique, is_login, visible_for_clients)
VALUES('custom_profession_copreltelecomparceiro', '{\"pt_br\":\"Qual sua profissão?\",\"en\":\"What is your profession?\",\"es\":\"¿Cuál es su profesión?\"}', 'choice', '{\"pt_br\":{\"Qual sua profissão?\":\"\",\"Produtor(a) rural\":\"Produtor(a) rural\",\"Imprensa\":\"Imprensa\",\"Estudante/Professor(a)\":\"Estudante/Professor(a)\",\"Técnico de Campo\":\"Técnico de Campo\",\"Funcionário Vence Tudo\":\"Funcionário Vence Tudo\",\"Familiar de Funcionário Vence Tudo\":\"Familiar de Funcionário Vence Tudo\",\"Demais Visitantes\":\"Demais Visitantes\"},\"en\":{\"What is your profession?\":\"\",\"Rural Producer\":\"Rural Producer\",\"Press\":\"Press\",\"Student/Teacher\":\"Student/Teacher\",\"Field Technician\":\"Field Technician\",\"Employee Vence Tudo\":\"Employee Vence Tudo\",\"Employee Family Vence Tudo\":\"Employee Family Vence Tudo\",\"Other Visitors\":\"Other Visitors\"},\"es\":{\"¿Cuál es su profesión?\":\"\",\"Productor(a) rural\":\"Productor(a) rural\",\"Prensa\":\"Prensa\",\"Estudiante/Profesor(a)\":\"Estudiante/Profesor(a)\",\"Técnico de Campo\":\"Técnico de Campo\",\"Empleado Vence Tudo\":\"Empleado Vence Tudo\",\"Familiar de Empleado Vence Tudo\":\"Familiar de Empleado Vence Tudo\",\"Demás Visitantes\":\"Demás Visitantes\"}}', '[{\"type\":\"required\",\"value\":true,\"message\":\"wspot.signup_page.field_required\",\"locale\":[\"pt_br\",\"en\",\"es\"]}]', NULL, 0, 0, '[\"copreltelecomparceiro\"]');");

        $this->addSql("INSERT INTO radius_wspotv3.custom_fields_template
(identifier, name, `type`, choices, validations, mask, is_unique, is_login, visible_for_clients)
VALUES('custom_opt_in_copreltelecomparceiro', '{\"pt_br\":\"Receber novidades sobre a Vence Tudo?\",\"en\":\"Receiving news about Vence Tudo?\",\"es\":\"¿Recibiendo noticias sobre Vence Tudo?\"}', 'choice', '{\"pt_br\":{\"Receber novidades sobre a Vence Tudo?\":\"\",\"Sim\":\"Sim\",\"Não\":\"Não\"},\"en\":{\"Receiving news about Vence Tudo?\":\"\",\"Yes\":\"Yes\",\"No\":\"No\"},\"es\":{\"¿Recibiendo noticias sobre Vence Tudo?\":\"\",\"Si\":\"Si\",\"No\":\"No\"}}', '[{\"type\":\"required\",\"value\":true,\"message\":\"wspot.signup_page.field_required\",\"locale\":[\"pt_br\",\"en\",\"es\"]}]', NULL, 0, 0, '[\"copreltelecomparceiro\"]');");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM radius_wspotv3.custom_fields_template WHERE identifier = 'custom_profession_copreltelecomparceiro'");
        $this->addSql("DELETE FROM radius_wspotv3.custom_fields_template WHERE identifier = 'custom_opt_in_copreltelecomparceiro'");

    }
}
