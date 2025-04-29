<?php

namespace Wideti\DomainBundle\Helpers;


use Symfony\Component\Form\Form;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\Guest\Guest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class FieldsHelper
{
    const FORM_COUNTRY_CODE_MOBILE = 'country-code-mobile';
    const FORM_COUNTRY_CODE_PHONE = 'country-code-phone';
    const FIELD_MOBILE = 'mobile';
    const FIELD_PHONE = 'phone';
    const fieldTypesMap = [
        'text'      => TextType::class,
        'textarea'  => TextareaType::class,
        'choice'    => ChoiceType::class,
        'checkbox'  => CheckboxType::class,
        'radio'     => RadioType::class,
        'date'      => DateType::class,
        'datetime'  => DateTimeType::class,
        'time'      => TimeType::class,
        'email'     => EmailType::class,
        'password'  => PasswordType::class,
        'repeated'  => RepeatedType::class,
        'integer'   => IntegerType::class,
        'number'    => NumberType::class,
        'hidden'    => HiddenType::class,
        'file'      => FileType::class,
        'submit'    => SubmitType::class,
        'url'       => UrlType::class,
    ];

    /**
     * @param array Field[] $fields
     * @param array string[] $neededFields
     * @return bool
     */
    public static function existsFields($fields = [], $neededFields = []) {
        $resultArray = array_filter($fields, function($field) use ($neededFields) {
            /** @var Field $field */
            return in_array($field->getIdentifier(), $neededFields);
        });

        return !empty($resultArray);
    }

    /**
     * @param Guest $guest
     * @param Form $form
     */
    public static function transformPhoneAndMobileGuest(Guest $guest, Form $form) {
        $properties = $guest->getProperties();

        if ($form->has(self::FORM_COUNTRY_CODE_MOBILE)
            && array_key_exists(self::FIELD_MOBILE, $properties)) {
            $mobileInput = $form->get(self::FORM_COUNTRY_CODE_MOBILE);
            $properties['dialCodeMobile'] = $mobileInput->getData();
        }

        if ($form->has(self::FORM_COUNTRY_CODE_PHONE)
            && array_key_exists(self::FIELD_PHONE, $properties)) {
            $phoneInput = $form->get(self::FORM_COUNTRY_CODE_PHONE);
            $properties['dialCodePhone'] = $phoneInput->getData();
        }

        $guest->setProperties($properties);
    }

    public static function isValidCountryCode($code) {
        if (!filter_var($code, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) !== false) {
            return false;
        }
    
        $validCountryCodes = [
            1,    // Estados Unidos, Canadá
            7,    // Rússia, Cazaquistão
            20,   // Egito
            27,   // África do Sul
            30,   // Grécia
            31,   // Países Baixos
            32,   // Bélgica
            33,   // França
            34,   // Espanha
            36,   // Hungria
            39,   // Itália
            40,   // Romênia
            41,   // Suíça
            43,   // Áustria
            44,   // Reino Unido
            45,   // Dinamarca
            46,   // Suécia
            47,   // Noruega
            48,   // Polônia
            49,   // Alemanha
            51,   // Peru
            52,   // México
            53,   // Cuba
            54,   // Argentina
            55,   // Brasil
            56,   // Chile
            57,   // Colômbia
            58,   // Venezuela
            60,   // Malásia
            61,   // Austrália
            62,   // Indonésia
            63,   // Filipinas
            64,   // Nova Zelândia
            65,   // Singapura
            66,   // Tailândia
            81,   // Japão
            82,   // Coreia do Sul
            84,   // Vietnã
            86,   // China
            90,   // Turquia
            91,   // Índia
            92,   // Paquistão
            93,   // Afeganistão
            94,   // Sri Lanka
            95,   // Myanmar
            98,   // Irã
            212,  // Marrocos
            213,  // Argélia
            216,  // Tunísia
            218,  // Líbia
            220,  // Gâmbia
            221,  // Senegal
            222,  // Mauritânia
            223,  // Mali
            224,  // Guiné
            225,  // Costa do Marfim
            226,  // Burkina Faso
            227,  // Níger
            228,  // Togo
            229,  // Benin
            230,  // Maurícias
            231,  // Libéria
            232,  // Serra Leoa
            233,  // Gana
            234,  // Nigéria
            235,  // Chade
            236,  // Centrafricana
            237,  // Camarões
            238,  // Cabo Verde
            239,  // São Tomé e Príncipe
            240,  // Guiné Equatorial
            241,  // Gabão
            242,  // Congo-Brazzaville
            243,  // Congo-Kinshasa
            244,  // Angola
            245,  // Guiné-Bissau
            246,  // Diego Garcia
            247,  // Ascensão
            248,  // Seychelles
            249,  // Sudão
            250,  // Ruanda
            251,  // Etiópia
            252,  // Somália
            253,  // Djibuti
            254,  // Quênia
            255,  // Tanzânia
            256,  // Uganda
            257,  // Burundi
            258,  // Moçambique
            260,  // Zâmbia
            261,  // Madagascar
            262,  // Reunião e Mayotte
            263,  // Zimbábue
            264,  // Namíbia
            265,  // Malawi
            266,  // Lesoto
            267,  // Botsuana
            268,  // Suazilândia
            269,  // Comores
            290,  // Santa Helena
            291,  // Eritreia
            297,  // Aruba
            298,  // Ilhas Faroe
            299,  // Groenlândia
            350,  // Gibraltar
            351,  // Portugal
            352,  // Luxemburgo
            353,  // Irlanda
            354,  // Islândia
            355,  // Albânia
            356,  // Malta
            357,  // Chipre
            358,  // Finlândia
            359,  // Bulgária
            370,  // Lituânia
            371,  // Letônia
            372,  // Estônia
            373,  // Moldávia
            374,  // Armênia
            375,  // Bielorrússia
            376,  // Andorra
            377,  // Mônaco
            378,  // San Marino
            379,  // Vaticano
            380,  // Ucrânia
            381,  // Montenegro
            382,  // Kosovo
            385,  // Croácia
            386,  // Eslovênia
            387,  // Bósnia e Herzegovina
            388,  // Macedônia do Norte
            389,  // Montenegro
            420,  // República Tcheca
            421,  // Eslováquia
            423,  // Liechtenstein
            500,  // Ilhas Malvinas
            501,  // Belize
            502,  // Guatemala
            503,  // El Salvador
            504,  // Honduras
            505,  // Nicarágua
            506,  // Costa Rica
            507,  // Panamá
            508,  // Saint Pierre e Miquelon
            509,  // Haiti
            591,  // Bolívia
            592,  // Guiana
            593,  // Equador
            594,  // Guiana Francesa
            595,  // Paraguai
            596,  // Martinica
            597,  // Suriname
            598,  // Uruguai
            599,  // Antilhas Holandesas
            670,  // Timor-Leste
            672,  // Território Antártico Australiano
            673,  // Brunei
            674,  // Nauru
            675,  // Papua Nova Guiné
            676,  // Tonga
            677,  // Solomon
            678,  // Vanuatu
            679,  // Fiji
            680,  // Palau
            681,  // Wallis e Futuna
            682,  // Cook Islands
            683,  // Niue
            684,  // Samoa Americana
            685,  // Samoa
            686,  // Kiribati
            687,  // Nova Caledônia
            688,  // Tuvalu
            689,  // Polinésia Francesa
            690,  // Tokelau
            691,  // Micronésia
            692,  // Ilhas Marshall
            850,  // Coreia do Norte
            852,  // Hong Kong
            853,  // Macau
            855,  // Camboja
            856,  // Laos
            960,  // Maldivas
            961,  // Líbano
            962,  // Jordânia
            963,  // Síria
            964,  // Iraque
            965,  // Kuwait
            966,  // Arábia Saudita
            967,  // Iémen
            968,  // Omã
            970,  // Palestina
            971,  // Emirados Árabes Unidos
            972,  // Israel
            973,  // Bahrein
            974,  // Qatar
            975,  // Bhutan
            976,  // Mongólia
            977,  // Nepal
            992,  // Tajiquistão
            993,  // Turcomenistão
            994,  // Azerbaijão
            995,  // Geórgia
            996,  // Quirguistão
            997,  // Cazaquistão
            998   // Uzbequistão
        ];
    
        $code = (int)$code;
        return in_array($code, $validCountryCodes);
    }
}