<?php

namespace stwon\CovPassCheck\HealthCertificate;

class Subject
{
    public function __construct(private string $firstName, private string $lastName, private string $dateOfBirth)
    {
        if (!preg_match('/^((19|20)\\d\\d(-\\d\\d){0,2})?$/', $this->dateOfBirth)) {
            throw new \InvalidArgumentException('Invalid date of birth: ' . $this->dateOfBirth);
        }
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getDateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    public function getFormattedDateOfBirth(): string
    {
        if (strlen($this->dateOfBirth) === 4) {
            return $this->dateOfBirth . '-XX-XX';
        }

        if (strlen($this->dateOfBirth) === 7) {
            return $this->dateOfBirth . '-XX';
        }

        return $this->dateOfBirth;
    }
}