<?php

namespace stwon\CovPassCheck;

use CBOR\ByteStringObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\OtherObject;
use CBOR\StringStream;
use CBOR\Tag;
use CBOR\TextStringObject;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use Exception;
use InvalidArgumentException;
use Mhauri\Base45;
use stwon\CovPassCheck\Exceptions\InvalidSignatureException;
use stwon\CovPassCheck\Exceptions\MissingHC1HeaderException;
use stwon\CovPassCheck\HealthCertificate\HealthCertificate;
use stwon\CovPassCheck\Trust\TrustStore;

class CovPassCheck
{
    // Allowed algorithms as per https://github.com/ehn-dcc-development/hcert-spec/blob/main/hcert_spec.md#332-signature-algorithm
    private const ALLOWED_ALGORITHMS = [
        ES256::ID => ES256::class,
        PS256::ID => PS256::class,
    ];

    public function __construct(private TrustStore $trustStore)
    {}

    private function makeCborDecoder(): Decoder
    {
        $otherObjectManager = new OtherObject\OtherObjectManager();

        $tagManager = new Tag\TagObjectManager();
        $tagManager->add(CoseSign1Tag::class);

        return new Decoder($tagManager, $otherObjectManager);
    }

    /**
     * @throws MissingHC1HeaderException
     * @throws Exception
     */
    private function extractCborContentFromCertificate(string $base45Data): string
    {
        if (!str_starts_with($base45Data, 'HC1:')) {
            throw new MissingHC1HeaderException();
        }

        $base45 = new Base45();
        $decodedData = $base45->decode(substr($base45Data, 4));

        // Content is deflated, inflate...
        if (ord($decodedData) === 0x78) {
            $decodedData = zlib_decode($decodedData);

            if ($decodedData === false) {
                throw new InvalidArgumentException('Invalid ZLib encoded data');
            }
        }

        return $decodedData;
    }

    /**
     * @throws MissingHC1HeaderException
     * @throws InvalidSignatureException
     * @throws Exception
     */
    public function readCertificate(string $base45Data): HealthCertificate
    {
        $decodedData = $this->extractCborContentFromCertificate($base45Data);

        $decoder = $this->makeCborDecoder();
        $coseObject = $decoder->decode(new StringStream($decodedData));

        if (!$coseObject instanceof CoseSign1Tag) {
            throw new Exception('No COSE Sign1 Tag found');
        }

        /** @var ListObject $coseMessages */
        $coseMessages = $coseObject->getValue();

        $coseHeaderByteArray = $coseMessages->get(0);

        if (!$coseHeaderByteArray instanceof ByteStringObject) {
            throw new InvalidArgumentException('Invalid COSE header');
        }

        $coseHeaderByteStream = new StringStream($coseHeaderByteArray->getValue());
        $coseHeader = $decoder->decode($coseHeaderByteStream)->getNormalizedData();

        $protectedHeader = $decoder->decode(new StringStream($coseMessages->get(0)->getValue()))->getNormalizedData();
        $unprotectedHeader = $coseMessages->get(1)->getNormalizedData();

        $decodedHeaders = $protectedHeader + $unprotectedHeader;
        $coseKid = base64_encode($decodedHeaders[4]);
        $cosePayloadByteArray = $coseMessages->get(2);

        if (!$cosePayloadByteArray instanceof ByteStringObject) {
            throw new InvalidArgumentException('Invalid COSE payload');
        }

        $cosePayloadByteStream = new StringStream($cosePayloadByteArray->getValue());
        $cosePayload = $decoder->decode($cosePayloadByteStream)->getNormalizedData();
        $coseSignature = $coseMessages->get(3)->getNormalizedData();
        $trustAnchor = $this->trustStore->getTrustAnchorByKid($coseKid);

        if ($trustAnchor === null) {
            throw new InvalidSignatureException('KID not found');
        }

        $cert = openssl_x509_read($trustAnchor->getCertificate());
        $publicKey = openssl_pkey_get_public($cert);
        $publicKeyData = openssl_pkey_get_details($publicKey);

        $key = Key::createFromData([
            Key::TYPE => Key::TYPE_EC2,
            Key::KID => $trustAnchor->getKid(),
            Ec2Key::DATA_CURVE => Ec2Key::CURVE_P256,
            Ec2Key::DATA_X => $publicKeyData['ec']['x'],
            Ec2Key::DATA_Y => $publicKeyData['ec']['y'],
        ]);

        $signatureAlgorithmClass = self::ALLOWED_ALGORITHMS[(int)$coseHeader[1]] ?? null;
        if (!$signatureAlgorithmClass) {
            throw new InvalidArgumentException('Invalid signature algorithm requested: ' . $coseHeader[1]);
        }

        $structure = new ListObject();
        $structure->add(new TextStringObject('Signature1'));
        $structure->add($coseHeaderByteArray);
        $structure->add(new ByteStringObject(''));
        $structure->add($cosePayloadByteArray);

        $signature = new $signatureAlgorithmClass();

        if (!$signature->verify((string)$structure, $key, $coseSignature)) {
            throw new InvalidSignatureException('Certificate signature is invalid');
        }

        return HealthCertificate::parseFromHcertV1($cosePayload);
    }
}
