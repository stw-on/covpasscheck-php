<?php

namespace stwon\CovPassCheck\Trust;

use JsonException;

abstract class TrustStore
{
    private ?array $cachedTrustAnchors = null;

    abstract public function fetchTrustAnchors(): array;

    /**
     * @return TrustAnchor[]
     */
    public function getTrustAnchors(): array
    {
        if ($this->cachedTrustAnchors === null) {
            $this->cachedTrustAnchors = $this->fetchTrustAnchors();
        }

        return $this->cachedTrustAnchors;
    }

    protected function clearTrustAnchorCache(): void
    {
        $this->cachedTrustAnchors = null;
    }

    /**
     * @param string $country
     * @return TrustAnchor[]
     */
    public function getTrustAnchorsByCountry(string $country): array
    {
        return array_values(
            array_filter(
                $this->getTrustAnchors(),
                static fn(TrustAnchor $anchor) => $anchor->getCountry() === $country,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * @param string $kid
     * @return TrustAnchor|null
     */
    public function getTrustAnchorByKid(string $kid): ?TrustAnchor
    {
        foreach ($this->getTrustAnchors() as $trustAnchor) {
            if ($trustAnchor->getKid() === $kid) {
                return $trustAnchor;
            }
        }

        return null;
    }
}