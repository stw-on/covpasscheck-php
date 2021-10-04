<?php

namespace stwon\CovPassCheck\HealthCertificate;

use DateTime;
use Exception;

class TestEntry
{
    public const TEST_RESULT_DETECTED = "260373001";
    public const TEST_RESULT_NOT_DETECTED = "260415000";

    /**
     * @throws Exception
     */
    public function __construct(
        private string          $target,
        private string          $testType,
        private ?string         $testName,
        private ?string         $testDeviceIdentifier,
        private DateTime|string $testDate,
        private string          $testResult,
        private ?string         $testingFacility,
        private string          $locationCountryCode,
        private string          $certificateIssuer,
        private string          $certificateId,
    )
    {
        if (is_string($this->testDate)) {
            $this->testDate = new DateTime($this->testDate);
        }
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getTestType(): string
    {
        return $this->testType;
    }

    /**
     * @return string|null
     */
    public function getTestName(): ?string
    {
        return $this->testName;
    }

    /**
     * @return string|null
     */
    public function getTestDeviceIdentifier(): ?string
    {
        return $this->testDeviceIdentifier;
    }

    /**
     * @return DateTime|string
     */
    public function getTestDate(): DateTime|string
    {
        return $this->testDate;
    }

    /**
     * @return string
     */
    public function getTestResult(): string
    {
        return $this->testResult;
    }

    /**
     * @return string
     */
    public function getTestingFacility(): string
    {
        return $this->testingFacility;
    }

    /**
     * @return string
     */
    public function getLocationCountryCode(): string
    {
        return $this->locationCountryCode;
    }

    /**
     * @return string
     */
    public function getCertificateIssuer(): string
    {
        return $this->certificateIssuer;
    }

    /**
     * @return string
     */
    public function getCertificateId(): string
    {
        return $this->certificateId;
    }

    public function isPositive(): bool
    {
        return $this->testResult === self::TEST_RESULT_DETECTED;
    }

    public function isNegative(): bool
    {
        return $this->testResult === self::TEST_RESULT_NOT_DETECTED;
    }
}