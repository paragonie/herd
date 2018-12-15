<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use ParagonIE\Certainty\{
    Exception\BundleException,
    Exception\CertaintyException,
    RemoteFetch
};
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Remote
 *
 * Encapsulates a Chronicle instance, used as a remote data source
 *
 * @package ParagonIE\Herd\Data
 */
class Remote
{
    /** @var string $certPath */
    protected $certPath;

    /** @var bool $isPrimary */
    protected $isPrimary = false;

    /** @var SigningPublicKey $publicKey */
    protected $publicKey;

    /** @var string $url */
    protected $url;

    /**
     * Remote constructor.
     *
     * @param string $url
     * @param SigningPublicKey $publicKey
     * @param bool $isPrimary
     * @param string $certPath
     */
    public function __construct(
        string $url,
        SigningPublicKey $publicKey,
        bool $isPrimary = false,
        string $certPath = ''
    ) {
        $this->url = $url;
        $this->publicKey = $publicKey;
        $this->isPrimary = $isPrimary;
        $this->certPath = $certPath;
    }

    /**
     * @return SigningPublicKey
     */
    public function getPublicKey(): SigningPublicKey
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * Lookup a hash in a Remote source. Return the PSR-7 response object
     * containing the Chronicle's API response and headers.
     *
     * @param string $summaryHash
     * @return ResponseInterface
     * @throws BundleException
     * @throws CertaintyException
     * @throws \SodiumException
     * @throws \TypeError
     */
    public function lookup(string $summaryHash): ResponseInterface
    {
        /** @var Client $http */
        $http = new Client();

        /** @var Response $response */
        $response = $http->get(
            $this->url . '/lookup/' . \urlencode($summaryHash),
            // We're going to use Certainty to always fetch the latest CACert bundle
            [
                'verify' => (new RemoteFetch($this->certPath))
                    ->getLatestBundle()
                    ->getFilePath()
            ]
        );
        if (!($response instanceof ResponseInterface)) {
            throw new \TypeError('Did not get a PSR-7 Response object');
        }
        return $response;
    }
}
