# CovPassCheck PHP

A PHP library to read and validate [EU Digital COVID Certificates](https://github.com/ehn-dcc-development/hcert-spec).


## Install

```shell
composer require stwon/covpasscheck-php
```

## Usage

Currently, the library only ships with a file based trust store. You need to download and store valid certificates
manually or implement your own custom `TrustStore` class. The `FileTrustStore` reads JSON files matching the schema
described [here](https://github.com/Digitaler-Impfnachweis/certification-apis/blob/master/dsc-update/README.md). Note
that you MUST NOT include the signature part (i.e. first line) of DSC TrustList Update API responses.

```php
$trustStore = new FileTrustStore('./certs.json');
$check = new CovPassCheck($trustStore);

try {
    // This is the scanned QR code content ↓
    $certificate = $check->readCertificate('HC1:...');

    $subject = $certificate->getSubject();

    if ($certificate->isCovered(Target::COVID19, HealthCertificate::TYPE_VACCINATION | HealthCertificate::TYPE_RECOVERY)) {
        $this->line($subject->getFirstName() . ' does conform to 2G rules.');
    } else {
        $this->line($subject->getFirstName() . ' does not conform to 2G rules.');
    }
} catch (InvalidSignatureException $exception) {
    // oh noo
}
```


## License

See [LICENSE.md](./LICENSE.md).