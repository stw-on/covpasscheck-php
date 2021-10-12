<?php

namespace stwon\CovPassCheck\HealthCertificate;

use DateTime;
use Exception;

class VaccinationEntry
{
    /**
     * @throws Exception
     */
    public function __construct(
        private string          $target,
        private string          $vaccineType,
        private string          $vaccineProduct,
        private string          $vaccineCompany,
        private int             $dosesReceived,
        private int             $dosesRequired,
        private DateTime|string $vaccinationDate,
        private string          $locationCountryCode,
        private string          $certificateIssuer,
        private string          $certificateId,
    )
    {
        if (is_string($this->vaccinationDate)) {
            $this->vaccinationDate = new DateTime($this->vaccinationDate);
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
    public function getVaccineType(): string
    {
        return $this->vaccineType;
    }

    /**
     * @return string
     */
    public function getVaccineProduct(): string
    {
        return $this->vaccineProduct;
    }

    /**
     * @return string
     */
    public function getVaccineCompany(): string
    {
        return $this->vaccineCompany;
    }

    /**
     * @return int
     */
    public function getDosesReceived(): int
    {
        return $this->dosesReceived;
    }

    /**
     * @return int
     */
    public function getDosesRequired(): int
    {
        return $this->dosesRequired;
    }

    /**
     * @return DateTime
     */
    public function getVaccinationDate(): DateTime
    {
        return $this->vaccinationDate;
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

    /**
     * @return bool
     */
    public function isFullyVaccinated(): bool
    {
        return $this->dosesReceived >= $this->dosesRequired;
    }
}