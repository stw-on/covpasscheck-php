<?php

namespace stwon\CovPassCheck\HealthCertificate;

use Carbon\Carbon;
use DateTime;
use Exception;

class RecoveryEntry
{
    /**
     * @throws Exception
     */
    public function __construct(
        private string           $target,
        private DateTime|string  $testDate,
        private string           $locationCountryCode,
        private DateTime|string  $certificateValidFrom,
        private DateTime|string  $certificateValidUntil,
        private string           $certificateIssuer,
        private string           $certificateId,
    )
    {
        if (is_string($this->testDate)) {
            $this->testDate = new DateTime($this->testDate);
        }

        if (is_string($this->certificateValidFrom)) {
            $this->certificateValidFrom = new DateTime($this->certificateValidFrom);
        }

        if (is_string($this->certificateValidUntil)) {
            $this->certificateValidUntil = new DateTime($this->certificateValidUntil);
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
     * @return DateTime
     */
    public function getTestDate(): DateTime
    {
        return $this->testDate;
    }

    /**
     * @return string
     */
    public function getLocationCountryCode(): string
    {
        return $this->locationCountryCode;
    }

    /**
     * @return DateTime
     */
    public function getCertificateValidFrom(): DateTime|string
    {
        return $this->certificateValidFrom;
    }

    /**
     * @return DateTime
     */
    public function getCertificateValidUntil(): DateTime
    {
        return $this->certificateValidUntil;
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

    public function isExpired(): bool
    {
        $now = Carbon::now();
        return $now->lessThan($this->certificateValidFrom) ||
            $now->greaterThan((new Carbon($this->certificateValidUntil))->endOfDay());
    }
}