<?php

namespace stwon\CovPassCheck\Trust;

use DateTime;

interface TrustAnchorContract
{
    /**
     * @return string
     */
    public function getCertificateType(): string;

    /**
     * @return string
     */
    public function getCountry(): string;

    /**
     * @return string
     */
    public function getKid(): string;

    /**
     * @return string
     */
    public function getCertificate(): string;

    /**
     * @return string
     */
    public function getSignature(): string;

    /**
     * @return string
     */
    public function getThumbprint(): string;

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime;
}