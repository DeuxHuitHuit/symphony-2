<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers FieldQuery
 */
final class FieldQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \FieldQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f`",
            $q->generateSQL(),
            'Simple new FieldQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \FieldQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `fields` AS `f`",
            $q->generateSQL(),
            'new FieldQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSectionFilter()
    {
        $q = (new \FieldQuery($this->db))->section(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`parent_section` = :f_parent_section",
            $q->generateSQL(),
            'new FieldQuery with ->section()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f_parent_section'], 'f_parent_section is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testFieldFilter()
    {
        $q = (new \FieldQuery($this->db))->field(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`id` = :f_id",
            $q->generateSQL(),
            'new FieldQuery test ->field()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f_id'], 'f_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testFieldsFilter()
    {
        $q = (new \FieldQuery($this->db))->fields([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`id` IN (?, ?, ?)",
            $q->generateSQL(),
            'new FieldQuery test ->fields()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values[0], 'f_id[0] is 4');
        $this->assertEquals(5, $values[1], 'f_id[1] is 5');
        $this->assertEquals(6, $values[2], 'f_id[2] is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testTypeFilter()
    {
        $q = (new \FieldQuery($this->db))->type('textbox');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`type` = :f_type",
            $q->generateSQL(),
            'new FieldQuery test ->type()'
        );
        $values = $q->getValues();
        $this->assertEquals('textbox', $values['f_type'], 'f_type is `textbox`');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testLocationFilter()
    {
        $q = (new \FieldQuery($this->db))->location('main');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` WHERE `f`.`location` = :f_location",
            $q->generateSQL(),
            'new FieldQuery test ->location()'
        );
        $values = $q->getValues();
        $this->assertEquals('main', $values['f_location'], 'f_location is `main`');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \FieldQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `fields` AS `f` ORDER BY `f`.`x` DESC",
            $q->generateSQL(),
            'new FieldQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
