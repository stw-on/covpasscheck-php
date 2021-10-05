<?php

namespace stwon\CovPassCheck\Trust;

use JsonException;

abstract class TrustStore
{
    private ?array $cachedTrustAnchors = null;

    abstract public function fetchTrustAnchors(): array;

    /**
     * @return TrustAnchorContract[]
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
     * @return TrustAnchorContract[]
     */
    public function getTrustAnchorsByCountry(string $country): array
    {
        return array_values(
            array_filter(
                $this->getTrustAnchors(),
                static fn(TrustAnchorContract $anchor) => $anchor->getCountry() === $country,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * @param string $kid
     * @return TrustAnchorContract|null
     */
    public function getTrustAnchorByKid(string $kid): ?TrustAnchorContract
    {
        foreach ($this->getTrustAnchors() as $trustAnchor) {
            if ($trustAnchor->getKid() === $kid) {
                return $trustAnchor;
            }
        }

        return null;
    }
}