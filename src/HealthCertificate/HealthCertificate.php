<?php

namespace stwon\CovPassCheck\HealthCertificate;

use Carbon\Carbon;
use Composer\Semver\Semver;
use DateTime;

class HealthCertificate
{
    public const TYPE_NONE = 0b000;
    public const TYPE_VACCINATION = 0b001;
    public const TYPE_TEST = 0b010;
    public const TYPE_RECOVERY = 0b100;

    /**
     * @param string $issuer
     * @param Carbon|null $issuedAt
     * @param Carbon|null $expiresAt
     * @param Subject $subject
     * @param VaccinationEntry[] $vaccinationEntries
     * @param TestEntry[] $testEntries
     * @param RecoveryEntry[] $recoveryEntries
     */
    private function __construct(
        private string  $issuer,
        private ?Carbon $issuedAt,
        private ?Carbon $expiresAt,
        private Subject $subject,
        private array   $vaccinationEntries,
        private array   $testEntries,
        private array   $recoveryEntries,
    )
    {
    }

    public static function parseFromHcertV1(array $data): HealthCertificate
    {
        // CWT hcert = -260, claim key 1, see https://github.com/ehn-dcc-development/hcert-spec/blob/main/hcert_spec.md
        $certificateData = $data['-260']['1'];

        if (!Semver::satisfies($certificateData['ver'], '^1.0.0')) {
            throw new \InvalidArgumentException('Invalid hcert version: ' . $certificateData['ver']);
        }

        $vaccinationEntries = [];
        $testEntries = [];
        $recoveryEntries = [];

        if (array_key_exists('v', $certificateData)) {
            $vaccinationEntries[] = new VaccinationEntry(
                $certificateData['v'][0]['tg'],
                $certificateData['v'][0]['vp'],
                $certificateData['v'][0]['mp'],
                $certificateData['v'][0]['ma'],
                (int)$certificateData['v'][0]['dn'],
                (int)$certificateData['v'][0]['sd'],
                $certificateData['v'][0]['dt'],
                $certificateData['v'][0]['co'],
                $certificateData['v'][0]['is'],
                $certificateData['v'][0]['ci'],
            );
        } else if (array_key_exists('t', $certificateData)) {
            $testEntries[] = new TestEntry(
                $certificateData['t'][0]['tg'],
                $certificateData['t'][0]['tt'],
                $certificateData['t'][0]['nm'] ?? null,
                $certificateData['t'][0]['ma'] ?? null,
                $certificateData['t'][0]['sc'],
                $certificateData['t'][0]['tr'],
                $certificateData['t'][0]['tc'] ?? null,
                $certificateData['t'][0]['co'],
                $certificateData['t'][0]['is'],
                $certificateData['t'][0]['ci'],
            );
        } else if (array_key_exists('r', $certificateData)) {
            $recoveryEntries[] = new RecoveryEntry(
                $certificateData['r'][0]['tg'],
                $certificateData['r'][0]['fr'],
                $certificateData['r'][0]['co'],
                $certificateData['r'][0]['df'],
                $certificateData['r'][0]['du'],
                $certificateData['r'][0]['is'],
                $certificateData['r'][0]['ci'],
            );
        }

        return new HealthCertificate(
            $data['1'],
            $data['6'] ? Carbon::createFromTimestamp($data['6']) : null,
            $data['4'] ? Carbon::createFromTimestamp($data['4']) : null,
            new Subject(
                $certificateData['nam']['gn'],
                $certificateData['nam']['fn'],
                $certificateData['dob'],
            ),
            $vaccinationEntries,
            $testEntries,
            $recoveryEntries,
        );
    }

    /**
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @return Carbon
     */
    public function getIssuedAt(): Carbon
    {
        return $this->issuedAt;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    /**
     * @return Subject
     */
    public function getSubject(): Subject
    {
        return $this->subject;
    }

    /**
     * @return VaccinationEntry[]
     */
    public function getVaccinationEntries(): array
    {
        return $this->vaccinationEntries;
    }

    /**
     * @return TestEntry[]
     */
    public function getTestEntries(): array
    {
        return $this->testEntries;
    }

    /**
     * @return RecoveryEntry[]
     */
    public function getRecoveryEntries(): array
    {
        return $this->recoveryEntries;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    /**
     * The 1.0 standard defines that each Health Certificate contains exactly
     * one type of proof.
     * @return int
     */
    public function getType(): int
    {
        if ($this->vaccinationEntries) {
            return self::TYPE_VACCINATION;
        }

        if ($this->testEntries) {
            return self::TYPE_TEST;
        }

        if ($this->recoveryEntries) {
            return self::TYPE_RECOVERY;
        }

        return self::TYPE_NONE;
    }

    /**
     * @param string $target One of the constants in the Target class
     * @param int $types
     * @return bool
     */
    public function isCovered(string $target, int $types): bool
    {
        if ($types & self::TYPE_VACCINATION) {
            foreach ($this->vaccinationEntries as $vaccinationEntry) {
                if ($vaccinationEntry->getTarget() === $target && $vaccinationEntry->isFullyVaccinated()) {
                    return true;
                }
            }
        }

        if ($types & self::TYPE_TEST) {
            foreach ($this->testEntries as $testEntry) {
                if ($testEntry->getTarget() === $target && $testEntry->isNegative()) {
                    return true;
                }
            }
        }

        if ($types & self::TYPE_RECOVERY) {
            foreach ($this->recoveryEntries as $recoveryEntry) {
                if ($recoveryEntry->getTarget() === $target && !$recoveryEntry->isExpired()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getCoverageExpiryDate(string $target, int $types): DateTime
    {
        $maxDate = Carbon::createFromTimestamp(0);

        if ($types & self::TYPE_VACCINATION) {
            foreach ($this->vaccinationEntries as $vaccinationEntry) {
                if ($vaccinationEntry->getTarget() === $target && $vaccinationEntry->isFullyVaccinated()) {
                    $maxDate = $maxDate->max($this->getExpiresAt());
                }
            }
        }

        if ($types & self::TYPE_TEST) {
            foreach ($this->testEntries as $testEntry) {
                if ($testEntry->getTarget() === $target && $testEntry->isNegative()) {
                    $maxDate = $maxDate->max($this->getExpiresAt());
                }
            }
        }

        if ($types & self::TYPE_RECOVERY) {
            foreach ($this->recoveryEntries as $recoveryEntry) {
                if ($recoveryEntry->getTarget() === $target && !$recoveryEntry->isExpired()) {
                    $maxDate = $maxDate->max($recoveryEntry->getCertificateValidUntil());
                }
            }
        }

        return $maxDate;
    }
}
