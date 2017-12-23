<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Tests;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use ParagonIE\Herd\{
    Config,
    Data\Local,
    Data\Remote,
    Herd
};
use PHPUnit\Framework\TestCase;

/**
 * Class HerdTest
 * @package ParagonIE\Herd\Tests
 */
class HerdTest extends TestCase
{
    /** @var EasyDB */
    private $db;

    public function setUp()
    {
        $this->db = Factory::create('sqlite:' . __DIR__ . '/empty.sql');
        $this->db->beginTransaction();
    }

    public function tearDown()
    {
        $this->db->rollBack();
    }

    /**
     * @covers Herd::getLatestSummaryHash()
     */
    public function testGetLatestSummaryHash()
    {
        $herd = new Herd(
            new Local($this->db),
            Config::fromFile(__DIR__ . '/config/empty.json')
        );
        $prev = '';

        $publickey = Base64UrlSafe::encode(random_bytes(32));
        for ($i = 0; $i < 10; ++$i) {
            $this->assertSame($prev, $herd->getLatestSummaryHash());

            $random = Base64UrlSafe::encode(random_bytes(32));
            $summary = Base64UrlSafe::encode(
                \sodium_crypto_generichash($random, $prev)
            );
            $signature = Base64UrlSafe::encode(random_bytes(32));
            $this->db->insert(
                'herd_history',
                [
                    'hash' => $random,
                    'prevhash' => $prev,
                    'summaryhash' => $summary,
                    'contents' => 'Iteration #' . $i,
                    'publickey' => $publickey,
                    'signature' => $signature,
                    'created' => date(DATE_ATOM)
                ]
            );
            $prev = $summary;
        }
        $this->assertSame($prev, $herd->getLatestSummaryHash());
    }

    /**
     * @covers Herd::selectRemote()
     */
    public function testSelectRemote()
    {
        $config = Config::fromFile(__DIR__ . '/config/valid.json');
        $this->assertTrue(count($config->getRemotes()) > 0);
        $herd = new Herd(
            new Local(Factory::create('sqlite:' . __DIR__ . '/empty.sql')),
            $config
        );

        $remote = $herd->selectRemote(true);
        $this->assertTrue($remote instanceof Remote);
        $this->assertFalse($remote->isPrimary());
    }

    /**
     * @covers Herd::selectRemote()
     */
    public function testSelectRemoteOnlyPrimaries()
    {
        $config = Config::fromFile(__DIR__ . '/config/only-primary-remote.json');
        $this->assertTrue(count($config->getRemotes()) > 0);
        $herd = new Herd(
            new Local(Factory::create('sqlite:' . __DIR__ . '/empty.sql')),
            $config
        );

        // This only has primaries.
        $remote = $herd->selectRemote(true);
        $this->assertTrue($remote instanceof Remote);
        $this->assertTrue($remote->isPrimary());
    }
}
