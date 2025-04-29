<?php
/**
 *
 * API: http://homolog.wspot.com.br/api
 *
 * Campos
 * GET /guests/fields
 *
 * Guests
 * POST /guests/bulk/{locale}
 **/

ini_set('auto_detect_line_endings', true);
ini_set('default_charset', "utf-8");
ini_set('mbstring.internal_encoding', "UTF-8");

// HOMOLOG API & KEY
$apiUrl = "https://{$argv[1]}.wspot.com.br/api/guests/pt_br";
$apiKey = $argv[2];
$groupId = "guest";

//// ENDPOINTS
$guestsEndpoint = "/guests";

// CSV IMPORT
$row = 1;

const STATUS = 0;
const GROUP = 1;
const IDIOMA = 2;
const PONTO_DE_ACESSO = 3;
const EMAIL = 4;
const NOME = 5;
const TELEFONE = 6;
const SENHA = 7;

if (($handle = fopen("{$argv[1]}.csv", "r")) !== false)
{
    while (($data = fgetcsv($handle,null, ";")) !== false)
    {
        if ($data[GROUP] == 'Funcionarios') {
            $data[GROUP] = 'employee';
        } elseif ($data[GROUP] == 'Visitantes') {
            $data[GROUP] = 'guest';
        } elseif ($data[GROUP] == 'Partners') {
            $data[GROUP] = 'custom_5b58d0f525752acf568b4568';
        } elseif ($data[GROUP] == 'Auditors') {
            $data[GROUP] = 'custom_5b58d119ff056def068b4568';
        } elseif ($data[GROUP] == 'TI-Admins') {
            $data[GROUP] = 'custom_5b5efdeb9880b157068b456b';
        } elseif ($data[GROUP] == 'Dispositivos Internos') {
            $data[GROUP] = 'custom_5b5f29fb75e2f94a7c8b456e';
        } else {
            $data[GROUP] = $data[GROUP];
        }

        $guest = [
            "password" => $data[SENHA],
            "group" => $data[GROUP],
            "status" => 1,
            "registrationMacAddress" => "F8-E7-1E-10-C8-90",
            "properties" => [
                "name" => utf8_encode(ucwords(strtolower($data[NOME]))),
                "email" => strtolower($data[EMAIL])
            ]
        ];
        $guestJson = json_encode($guest);
        //post na API
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-TOKEN: ' . $apiKey,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($guestJson)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $guestJson);
        $response = curl_exec($ch);

        echo $response . PHP_EOL . PHP_EOL;
    }
    fclose($handle);
}

function formatDocument($document)
{
    if (!preg_match('/^[0-9]{3}.[0-9]{3}.[0-9]{3}-[0-9]{2}/', $document))
        return $document;

    $documentParts = explode(".", $document);
    $newDoc = join("", $documentParts);
    $documentParts = explode("-", $newDoc);
    $newDoc = join("", $documentParts);

    return $newDoc;
}

function formataPhone($phone)
{
    $str = preg_replace('/[^a-zA-Z0-9 ]/', '', $phone);

    if (strlen($str) == 10)
        $str = "0".$str;

    return $str;
}