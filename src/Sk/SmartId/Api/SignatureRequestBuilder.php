<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */

namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\SessionRequest;
use Sk\SmartId\Api\Data\SessionResponse;
use Sk\SmartId\Api\Data\SemanticsIdentifier;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SignableData;
use Sk\SmartId\Api\Data\SmartIdSignatureResponse;
use Sk\SmartId\Exception\InvalidParametersException;
use Sk\SmartId\Exception\TechnicalErrorException;

class SignatureRequestBuilder extends SmartIdRequestBuilder
{

  /**
   * @var SemanticsIdentifier
   */
  private $semanticsIdentifier;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var SignableData
   */
  private $dataToSign;

  /**
   * @var array
   */
  private $allowedInteractionsOrder = [];

  /**
   * @var string
   */
  private $nonce;

  /**
   * @param SmartIdConnector $connector
   * @param SessionStatusPoller $sessionStatusPoller
   */
  public function __construct(SmartIdConnector $connector, SessionStatusPoller $sessionStatusPoller)
  {
    parent::__construct($connector, $sessionStatusPoller);
  }

  /**
   * @param string $documentNumber
   * @return $this
   */
  public function withDocumentNumber(string $documentNumber): self
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }

  /**
   * @param SemanticsIdentifier $semanticsIdentifier
   * @return $this
   */
  public function withSemanticsIdentifier(SemanticsIdentifier $semanticsIdentifier): self
  {
    $this->semanticsIdentifier = $semanticsIdentifier;
    return $this;
  }

  /**
   * @param string $semanticsIdentifierAsString
   * @return $this
   */
  public function withSemanticsIdentifierAsString(string $semanticsIdentifierAsString): self
  {
    $this->semanticsIdentifier = SemanticsIdentifier::fromString($semanticsIdentifierAsString);
    return $this;
  }

  /**
   * @param SignableData $dataToSign
   * @return $this
   */
  public function withSignableData(SignableData $dataToSign): self
  {
    $this->dataToSign = $dataToSign;
    return $this;
  }

  /**
   * @param string $certificateLevel
   * @return $this
   */
  public function withCertificateLevel(string $certificateLevel): self
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  public function withAllowedInteractionsOrder(array $allowedInteractionsOrder): self
  {
    $this->allowedInteractionsOrder = $allowedInteractionsOrder;
    return $this;
  }

  /**
   * @param string $nonce
   * @return $this
   */
  public function withNonce(string $nonce): self
  {
    $this->nonce = $nonce;
    return $this;
  }

  /**
   * @param string $relyingPartyUUID
   * @return $this
   */
  public function withRelyingPartyUUID(string $relyingPartyUUID): self
  {
    parent::withRelyingPartyUUID($relyingPartyUUID);
    return $this;
  }

  /**
   * @param string $relyingPartyName
   * @return $this
   */
  public function withRelyingPartyName(string $relyingPartyName): self
  {
    parent::withRelyingPartyName($relyingPartyName);
    return $this;
  }

  /**
   * @return SmartIdSignatureResponse
   */
  public function sign(): SmartIdSignatureResponse
  {
    $response = $this->getSignatureResponse();
    $sessionStatus = $this->getSessionStatusPoller()
      ->fetchFinalSessionStatus($response->getSessionID());
    $this->validateSessionStatus($sessionStatus);
    return $this->createSmartIdSignatureResponse($sessionStatus);
  }

  /**
   * @return string
   */
  public function startAuthenticationAndReturnSessionId(): string
  {
    $response = $this->getSignatureResponse();
    return $response->getSessionID();
  }

  /**
   * @return SessionRequest
   */
  private function createSignatureSessionRequest(): SessionRequest
  {
    $request = new SessionRequest();
    $request->setRelyingPartyUUID($this->getRelyingPartyUUID())
      ->setRelyingPartyName($this->getRelyingPartyName())
      ->setCertificateLevel($this->certificateLevel)
      ->setHashType($this->getHashTypeString())
      ->setHash($this->getHashInBase64())
      ->setAllowedInteractionsOrder($this->allowedInteractionsOrder)
      ->setNonce($this->nonce)
      ->setNetworkInterface($this->getNetworkInterface());
    return $request;
  }

  /**
   * @return string
   */
  private function getHashTypeString(): string
  {
    return $this->dataToSign->getHashType();
  }

  /**
   * @return string
   */
  private function getHashInBase64(): string
  {
    return $this->dataToSign->calculateHashInBase64();
  }

  /**
   * @return SessionResponse
   */
  private function getSignatureResponse(): SessionResponse
  {
    $this->validateParameters();
    $request = $this->createSignatureSessionRequest();

    if (!empty($this->documentNumber)) {
      return $this->getConnector()
        ->sign($this->documentNumber, $request);
    } else {
      return $this->getConnector()
        ->signWithSemanticsIdentifier($this->semanticsIdentifier, $request);
    }
  }

  /**
   * @throws InvalidParametersException
   */
  protected function validateParameters()
  {
    parent::validateParameters();
    if (!isset($this->documentNumber) && !isset($this->semanticsIdentifier)) {
      throw new InvalidParametersException('Either document number or semantics identifier must be set');
    }

    $this->validateSemanticsIdentifierIfSet();

    if (!$this->isSignableDataSet()) {
      throw new InvalidParametersException('Signable data must be set');
    }

    if (!isset($this->allowedInteractionsOrder) or sizeof($this->allowedInteractionsOrder) == 0) {
      throw new InvalidParametersException('The interaction options need to be specified');
    }
    $this->verifyInteractionsIfSet();
  }

  /**
   * @return bool
   */
  private function isSignableDataSet(): bool
  {
    return isset($this->dataToSign);
  }

  /**
   * @param SessionStatus $sessionStatus
   * @throws TechnicalErrorException
   */
  private function validateSessionStatus(SessionStatus $sessionStatus)
  {
    if ($sessionStatus->getSignature() === null) {
      throw new TechnicalErrorException('Signature was not present in the response');
    }
    if ($sessionStatus->getCert() === null) {
      throw new TechnicalErrorException('Certificate was not present in the response');
    }
  }

  /**
   * @param SessionStatus $sessionStatus
   * @return SmartIdSignatureResponse
   */
  private function createSmartIdSignatureResponse(SessionStatus $sessionStatus): SmartIdSignatureResponse
  {
    $sessionResult = $sessionStatus->getResult();
    $sessionSignature = $sessionStatus->getSignature();
    $sessionCertificate = $sessionStatus->getCert();

    $response = new SmartIdSignatureResponse();
    $response->setEndResult($sessionResult->getEndResult())
      ->setState($sessionStatus->getState())
      ->setSignedData($this->getDataToSign())
      ->setIgnoredProperties($sessionStatus->getIgnoredProperties())
      ->setInteractionFlowUsed($sessionStatus->getInteractionFlowUsed())
      ->setValueInBase64($sessionSignature->getValue())
      ->setAlgorithmName($sessionSignature->getAlgorithm())
      ->setCertificate($sessionCertificate->getValue())
      ->setCertificateLevel($sessionCertificate->getCertificateLevel())
      ->setDocumentNumber($sessionResult->getDocumentNumber());
    return $response;
  }

  /**
   * @return string
   */
  private function getDataToSign(): string
  {
    return $this->dataToSign->getDataToSign();
  }

  /**
   * @return SemanticsIdentifier
   */
  public function getSemanticsIdentifier(): SemanticsIdentifier
  {
    return $this->semanticsIdentifier;
  }

  protected function validateSemanticsIdentifierIfSet(): void
  {
    if (isset($this->semanticsIdentifier)) {
      $this->semanticsIdentifier->validate();
    }
  }

  protected function verifyInteractionsIfSet(): void
  {
    foreach ($this->allowedInteractionsOrder as $interaction) {
      $interaction->validate();
    }
  }
}
