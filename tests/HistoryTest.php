<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Tests;

use GuzzleHttp\Exception\ConnectException;
use ParagonIE\Certainty\{
    Exception\CertaintyException,
    RemoteFetch
};
use ParagonIE\EasyDB\{
    EasyDB,
    Factory
};
use ParagonIE\Herd\{
    Config,
    Data\Local,
    Herd,
    History
};
use PHPUnit\Framework\TestCase;

/**
 * Class HistoryTest
 * @package ParagonIE\Herd\Tests
 */
class HistoryTest extends TestCase
{
    /** @var EasyDB */
    private $db;

    public function setUp()
    {
        $this->db = Factory::create('sqlite:' . __DIR__ . '/empty.sql');
        $directory = dirname(__DIR__) . '/data/certs';
        try {
            (new RemoteFetch($directory))
                ->getLatestBundle()
                ->getFilePath();
        } catch (CertaintyException $ex) {
            $this->fail('Test failed: could not download CACert bundle');
        } catch (ConnectException $ex) {
            $this->markTestSkipped('Cannot connect using TLSv1.2');
        }
    }

    /**
     * This may take some time to complete!
     *
     * @covers History::transcribe()
     */
    public function testTranscribe()
    {
        $config = Config::fromFile(__DIR__ . '/config/public-test.json');
        $this->assertTrue(count($config->getRemotes()) > 0);
        $herd = new Herd(
            new Local(Factory::create('sqlite:' . __DIR__ . '/empty.sql')),
            $config
        );
        $history = new History($herd);

        $this->assertEquals('', $herd->getLatestSummaryHash());
        $this->assertTrue($history->transcribe());
        $this->assertNotEquals('', $herd->getLatestSummaryHash());
        $this->db->query('DELETE FROM herd_history');
        $this->assertEquals('', $herd->getLatestSummaryHash());
    }
}
