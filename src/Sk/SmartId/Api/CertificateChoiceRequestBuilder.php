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
use Sk\SmartId\Api\Data\SmartIdCertificateChoiceResponse;
use Sk\SmartId\Exception\InvalidParametersException;
use Sk\SmartId\Exception\TechnicalErrorException;

class CertificateChoiceRequestBuilder extends SmartIdRequestBuilder
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
   * @return SmartIdCertificateChoiceResponse
   */
  public function chooseCertificate(): SmartIdCertificateChoiceResponse
  {
    $response = $this->getCertificateChoiceResponse();
    $sessionStatus = $this->getSessionStatusPoller()
      ->fetchFinalSessionStatus($response->getSessionID());
    $this->validateSessionStatus($sessionStatus);
    return $this->createSmartIdCertificateChoiceResponse($sessionStatus);
  }

  /**
   * @return string
   */
  public function startAuthenticationAndReturnSessionId(): string
  {
    $response = $this->getCertificateChoiceResponse();
    return $response->getSessionID();
  }

  /**
   * @return SessionRequest
   */
  private function createCertificateChoiceSessionRequest(): SessionRequest
  {
    $request = new SessionRequest();
    $request->setRelyingPartyUUID($this->getRelyingPartyUUID())
      ->setRelyingPartyName($this->getRelyingPartyName())
      ->setCertificateLevel($this->certificateLevel)
      ->setAllowedInteractionsOrder($this->allowedInteractionsOrder)
      ->setNonce($this->nonce)
      ->setNetworkInterface($this->getNetworkInterface());
    return $request;
  }

  /**
   * @return SessionResponse
   */
  private function getCertificateChoiceResponse(): SessionResponse
  {
    $this->validateParameters();
    $request = $this->createCertificateChoiceSessionRequest();

    if (!empty($this->documentNumber)) {
      return $this->getConnector()
        ->chooseCertificate($this->documentNumber, $request);
    } else {
      return $this->getConnector()
        ->chooseCertificateWithSemanticsIdentifier($this->semanticsIdentifier, $request);
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
    $this->verifyInteractionsIfSet();
  }

  /**
   * @param SessionStatus $sessionStatus
   * @throws TechnicalErrorException
   */
  private function validateSessionStatus(SessionStatus $sessionStatus)
  {
    if ($sessionStatus->getResult()->getDocumentNumber() === null) {
      throw new TechnicalErrorException('Document number was not present in the response');
    }
    if ($sessionStatus->getCert() === null) {
      throw new TechnicalErrorException('Certificate was not present in the response');
    }
  }

  /**
   * @param SessionStatus $sessionStatus
   * @return SmartIdCertificateChoiceResponse
   */
  private function createSmartIdCertificateChoiceResponse(SessionStatus $sessionStatus): SmartIdCertificateChoiceResponse
  {
    $sessionResult = $sessionStatus->getResult();
    $sessionCertificate = $sessionStatus->getCert();

    $response = new SmartIdCertificateChoiceResponse();
    $response->setEndResult($sessionResult->getEndResult())
      ->setState($sessionStatus->getState())
      ->setIgnoredProperties($sessionStatus->getIgnoredProperties())
      ->setInteractionFlowUsed($sessionStatus->getInteractionFlowUsed())
      ->setCertificate($sessionCertificate->getValue())
      ->setCertificateLevel($sessionCertificate->getCertificateLevel())
      ->setDocumentNumber($sessionResult->getDocumentNumber());
    return $response;
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
