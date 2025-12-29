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

namespace Sk\SmartId\Api\Data;

class SmartIdCertificateChoiceResponse extends PropertyMapper
{
  /**
   * @var string
   */
  private $endResult;

  /**
   * @var string
   */
  private $certificate;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var string
   */
  private $state;

  /**
   * @var string
   */
  private $interactionFlowUsed;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @return string
   */
  public function getEndResult(): ?string
  {
    return $this->endResult;
  }

  /**
   * @param string $endResult
   * @return $this
   */
  public function setEndResult(string $endResult): self
  {
    $this->endResult = $endResult;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificate(): ?string
  {
    return $this->certificate;
  }

  /**
   * @return array
   */
  public function getParsedCertificate(): array
  {
    return CertificateParser::parseX509Certificate($this->certificate);
  }

  /**
   * @return AuthenticationCertificate
   */
  public function getCertificateInstance(): AuthenticationCertificate
  {
    $parsed = CertificateParser::parseX509Certificate($this->certificate);
    return new AuthenticationCertificate($parsed);
  }

  /**
   * @param string|null $certificate
   * @return $this
   */
  public function setCertificate(?string $certificate): self
  {
    $this->certificate = $certificate;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificateLevel(): string
  {
    return $this->certificateLevel;
  }

  /**
   * @param string|null $certificateLevel
   * @return $this
   */
  public function setCertificateLevel(?string $certificateLevel): self
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @param string $state
   * @return $this
   */
  public function setState(string $state): self
  {
    $this->state = $state;
    return $this;
  }

  /**
   * @return string
   */
  public function getState(): string
  {
    return $this->state;
  }

  /**
   * @param array|null $ignoredProperties
   * @return SmartIdAuthenticationResponse
   */
  public function setIgnoredProperties(?array $ignoredProperties): self
  {
    $this->ignoredProperties = $ignoredProperties;
    return $this;
  }

  /**
   * @return string
   */
  public function getInteractionFlowUsed(): string
  {
    return $this->interactionFlowUsed;
  }

  /**
   * @param string|null $interactionFlowUsed
   * @return SmartIdAuthenticationResponse
   */
  public function setInteractionFlowUsed(?string $interactionFlowUsed): self
  {
    $this->interactionFlowUsed = $interactionFlowUsed;
    return $this;
  }

  /**
   * @return bool
   */
  public function isRunningState(): bool
  {
    return strcasecmp(SessionStatusCode::RUNNING, $this->state) == 0;
  }

  public function setDocumentNumber(?string $documentNumber): self
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }

  /**
   * @return string
   */
  public function getDocumentNumber(): ?string
  {
    return $this->documentNumber;
  }
}
