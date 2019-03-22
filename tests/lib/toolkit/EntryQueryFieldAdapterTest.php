<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryFieldAdapter
 */
final class EntryQueryFieldAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryFieldAdapter
     */
    private function createOperations(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryFieldAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createOperations();
        $o->filter($q, ['test']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`f1`.`value` = :f1_value)",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['f1_value'], 'f1_value is test');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSortAsc()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createOperations();
        $o->sort($q, 'asc');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` ORDER BY `f1`.`value` ASC",
            $q->generateSQL(),
            'Simple asc sort ->sort(asc)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortRandom()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createOperations();
        $o->sort($q, 'rand');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY RAND()",
            $q->generateSQL(),
            'Simple random ->sort(random)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
