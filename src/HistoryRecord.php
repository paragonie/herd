<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\Herd\Data\Cacheable;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;

/**
 * Class HistoryRecord
 *
 * Encapsulates an entry in the herd_history table
 *
 * @package ParagonIE\Herd
 */
class HistoryRecord implements Cacheable
{
    /** @var bool $accepted */
    protected $accepted = false;

    /** @var string $contents */
    protected $contents = '';

    /** @var string $hash */
    protected $hash = '';

    /** @var string $previousHash */
    protected $previousHash = '';

    /** @var SigningPublicKey $publicKey */
    protected $publicKey;

    /** @var string $signature */
    protected $signature = '';

    /** @var string $summaryHash */
    protected $summaryHash = '';

    /**
     * HistoryRecord constructor.
     *
     * @param SigningPublicKey $publicKey
     * @param string $signature
     * @param string $contents
     * @param string $hash
     * @param string $summaryHash
     * @param string $prevHash
     * @param bool $accepted
     */
    public function __construct(
        SigningPublicKey $publicKey,
        string $signature,
        string $contents,
        string $hash,
        string $summaryHash,
        string $prevHash,
        bool $accepted = false
    ) {
        $this->publicKey = $publicKey;
        $this->signature = $signature;
        $this->contents = $contents;
        $this->hash = $hash;
        $this->summaryHash = $summaryHash;
        $this->previousHash = $prevHash;
        $this->accepted = $accepted;
    }
}
