<?php

use Sk\SmartId\Api\Data\Interaction;
use Sk\SmartId\Api\Data\SemanticsIdentifier;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Client;

require_once 'vendor/autoload.php';


$client = new Client();
$client
    ->setRelyingPartyUUID('00000000-0000-0000-0000-000000000000') // In production replace with your UUID
    ->setRelyingPartyName('DEMO') // In production replace with your name
    ->setHostUrl('https://sid.demo.sk.ee/smart-id-rp/v2/') // In production replace with production service URL
    // in production replace with correct server SSL key
    ->setPublicSslKeys("sha256//Ps1Im3KeB0Q4AlR+/J9KFd/MOznaARdwo4gURPCLaVA=");


$semanticsIdentifier = SemanticsIdentifier::builder()
    ->withSemanticsIdentifierType('PNO')
    ->withCountryCode('LT')
    ->withIdentifier('30303039914')
    ->build();

try {
    $resp = $client->signature()->createCertificateChoice()
        ->withSemanticsIdentifier($semanticsIdentifier)
        ->withCertificateLevel('QUALIFIED')
        ->chooseCertificate();
} catch (\Exception $e) { // Use exceptions below
    throw new RuntimeException("Smart-ID authentication process failed for uncertain reason: " . $e);
}

$data = new SignableData('12312312312312');
$data->setHashType('SHA256');

try {
    $resp = $client->signature()->createSignature()
        //->withDocumentNumber($resp->getDocumentNumber())
        ->withSemanticsIdentifier($semanticsIdentifier)
        ->withCertificateLevel('QUALIFIED')
        ->withSignableData($data)
        ->withAllowedInteractionsOrder([
            Interaction::ofTypeVerificationCodeChoice('Kood')
        ])
        ->sign();
} catch (\Exception $e) { // Use exceptions below
    throw new RuntimeException("Smart-ID authentication process failed for uncertain reason: " . $e);
}

var_dump($resp);
