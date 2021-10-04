<?php

namespace stwon\CovPassCheck\Trust;

class TrustAnchor
{
    public function __construct(
        private string $certificateType,
        private string $country,
        private string $kid,
        private string $certificate,
        private string $signature,
        private string $thumbprint,
        private \DateTime $timestamp,
    )
    {}

    /**
     * @return string
     */
    public function getCertificateType(): string
    {
        return $this->certificateType;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getKid(): string
    {
        return $this->kid;
    }

    /**
     * @return string
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function getThumbprint(): string
    {
        return $this->thumbprint;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}