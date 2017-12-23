<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Tests;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
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
        $this->db->beginTransaction();
    }

    public function tearDown()
    {
        $this->db->rollBack();
    }

    /**
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

        $this->assertTrue($history->transcribe(false));
    }
}
