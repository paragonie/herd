<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use ParagonIE\Certainty\{
    Exception\BundleException,
    RemoteFetch
};
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\Data\{
    Local,
    Remote
};
use ParagonIE\Herd\Exception\{
    ChronicleException,
    EmptyValueException,
    EncodingError,
    FilesystemException
};
use ParagonIE\Sapient\{
    Adapter\Guzzle,
    CryptographyKeys\SigningPublicKey,
    Sapient
};

/**
 * Class Herd
 * @package ParagonIE\Herd
 */
class Herd
{
    /** @var Config $config */
    protected $config;

    /** @var Local */
    protected $local;

    /** @var array<int, Remote> $remotes */
    protected $remotes = [];

    /** @var Sapient $sapient */
    protected $sapient;

    /**
     * Herd constructor.
     *
     * @param Local $local
     * @param Config|null $config
     * @param Sapient|null $sapient
     *
     * @throws EncodingError
     * @throws FilesystemException
     */
    public function __construct(
        Local $local,
        Config $config = null,
        Sapient $sapient = null
    ) {
        $this->local = $local;
        if (!$config) {
            $config = $this->local->loadConfigFile();
        }
        $this->config = $config;
        foreach ($config->getRemotes() as $rem) {
            if (!\is_string($rem['public-key']) || !\is_string($rem['url'])) {
                continue;
            }
            $this->addRemote(
                new Remote(
                    $rem['url'],
                    new SigningPublicKey(
                        Base64UrlSafe::decode($rem['public-key'])
                    ),
                    !empty($rem['primary'])
                )
            );
        }
        if (!$sapient) {
            $sapient = new Sapient(new Guzzle(new Client()));
        }
        $this->sapient = $sapient;
    }

    /**
     * @param Remote $remote
     * @return self
     */
    public function addRemote(Remote $remote): self
    {
        $this->remotes[] = $remote;
        return $this;
    }

    /**
     * @return array<int, Remote>
     */
    public function getAllRemotes(): array
    {
        return $this->remotes;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return EasyDB
     */
    public function getDatabase(): EasyDB
    {
        return $this->local->getDatabase();
    }

    /**
     * @return string
     */
    public function getLatestSummaryHash(): string
    {
        /** @var string $summaryHash */
        $summaryHash = (string) $this->local->getDatabase()->cell(
            "SELECT summaryhash FROM herd_history ORDER BY id DESC LIMIT 1"
        );
        if (!empty($summaryHash)) {
            return (string) $summaryHash;
        }
        return '';
    }

    /**
     * Get an array of updates from a given Remote source.
     *
     * @param string $summaryHash
     * @param Remote|null $target
     * @return array<int, array<string, string>>
     * @throws BundleException
     * @throws ChronicleException
     * @throws EmptyValueException
     */
    public function getUpdatesSince(string $summaryHash = '', Remote $target = null): array
    {
        if (empty($summaryHash)) {
            $summaryHash = $this->getLatestSummaryHash();
        }
        if (!$target) {
            $target = $this->selectRemote(true);
        }


        if (!empty($summaryHash)) {
            // Only get the new entries
            $url = $target->getUrl() . '/since/' . \urlencode($summaryHash);
        } else {
            // First run: Grab everything
            $url = $target->getUrl() . '/export';
        }

        /** @var Client $http */
        $http = new Client();

        /** @var Response $response */
        $response = $http->get(
            $url,
            // We're going to use Certainty to always fetch the latest CACert bundle
            [
                'verify' => (new RemoteFetch())
                    ->getLatestBundle()
                    ->getFilePath()
            ]
        );

        /** @var array $decoded */
        $decoded = $this->sapient->decodeSignedJsonResponse(
            $response,
            $target->getPublicKey()
        );

        // If the status was anything except "OK", raise the alarm:
        if ($decoded['status'] !== 'OK') {
            if (\is_string($decoded['message'])) {
                throw new ChronicleException($decoded['message']);
            }
            throw new ChronicleException('An unknown error has occurred with the Chronicle');
        }
        if (\is_array($decoded['results'])) {
            return $decoded['results'];
        }
        return [];
    }

    /**
     * Select a random Remote. If you pass TRUE to the first argument, it will
     * select a non-primary Remote source. Otherwise, it just grabs one at
     * complete random. If no non-primary Remote sources are available, you will
     * end up getting the primary one anyway.
     *
     * The purpose of selecting non-primary Remotes was to prevent overloading
     * the central repository and instead querying a replication instance of the
     * upstream Chronicle.
     *
     * @param bool $notPrimary
     * @return Remote
     * @throws EmptyValueException
     */
    public function selectRemote(bool $notPrimary = false): Remote
    {
        if (empty($this->remotes)) {
            throw new EmptyValueException('No remote sources are configured');
        }
        $count = \count($this->remotes);
        $select = \random_int(0, $count - 1);

        if ($notPrimary) {
            // We don't want primary remotes if we can help it
            $secondary = [];
            for ($i = 0; $i < $count; ++$i) {
                if (!$this->remotes[$i]->isPrimary()) {
                    $secondary[] = $i;
                }
            }
            if (!empty($secondary)) {
                // We can return a secondary one!
                // Otherwise, we'll have to return a primary.
                $subcount = \count($secondary);
                $select = $secondary[\random_int(0, $subcount - 1)];
            }
        }
        return $this->remotes[$select];
    }
}
