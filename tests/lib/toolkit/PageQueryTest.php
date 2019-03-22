<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers PageQuery
 */
final class PageQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \PageQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p`",
            $q->generateSQL(),
            'Simple new PageQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \PageQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `pages` AS `p`",
            $q->generateSQL(),
            'new PageQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testPageFilter()
    {
        $q = (new \PageQuery($this->db))->page(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`id` = :p_id",
            $q->generateSQL(),
            'new PageQuery test ->page()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['p_id'], 'p_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testPagesFilter()
    {
        $q = (new \PageQuery($this->db))->pages([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`id` IN (?, ?, ?)",
            $q->generateSQL(),
            'new PageQuery test ->pages()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values[0], 'p_id[0] is 4');
        $this->assertEquals(5, $values[1], 'p_id[1] is 5');
        $this->assertEquals(6, $values[2], 'p_id[2] is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testHandleFilter()
    {
        $q = (new \PageQuery($this->db))->handle('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`handle` = :p_handle",
            $q->generateSQL(),
            'new PageQuery test ->handle()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['p_handle'], 'p_handle is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testPathFilter()
    {
        $q = (new \PageQuery($this->db))->path('x');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` WHERE `p`.`path` = :p_path",
            $q->generateSQL(),
            'new PageQuery test ->path()'
        );
        $values = $q->getValues();
        $this->assertEquals('x', $values['p_path'], 'p_path is x');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \PageQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `pages` AS `p` ORDER BY `p`.`x` DESC",
            $q->generateSQL(),
            'new PageQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
