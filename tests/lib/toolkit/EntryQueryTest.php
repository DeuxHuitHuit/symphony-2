<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQuery
 */
final class EntryQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = (new \EntryQuery($this->db));
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'Simple new EntryQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCount()
    {
        $q = (new \EntryQuery($this->db))->projection(['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSectionFilter()
    {
        $q = (new \EntryQuery($this->db))->section(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`section_id` = :e_section_id",
            $q->generateSQL(),
            'new EntryQuery with ->section()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['e_section_id'], 'e_section_id is 4');
        $this->assertEquals(1, count($values), '1 value');
        $this->assertEquals(4, $q->sectionId(), 'Section id is 4');
    }

    public function testEntryFilter()
    {
        $q = (new \EntryQuery($this->db))->entry(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` = :e_id",
            $q->generateSQL(),
            'new EntryQuery test ->entry()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['e_id'], 'e_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEntriesFilter()
    {
        $q = (new \EntryQuery($this->db))->entries([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` IN (?, ?, ?)",
            $q->generateSQL(),
            'new EntryQuery test ->entries()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values[0], 'e_id[0] is 4');
        $this->assertEquals(5, $values[1], 'e_id[1] is 5');
        $this->assertEquals(6, $values[2], 'e_id[2] is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testInnerJoinField()
    {
        $q = (new \EntryQuery($this->db))->innerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` INNER JOIN `entries_data_4` AS `t_4` ON `e`.`id` = `t_4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->innerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testJoinField()
    {
        $q = (new \EntryQuery($this->db))->joinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` JOIN `entries_data_4` AS `t_4` ON `e`.`id` = `t_4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->joinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testLeftJoinField()
    {
        $q = (new \EntryQuery($this->db))->leftJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_4` AS `t_4` ON `e`.`id` = `t_4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->leftJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testOuterJoinField()
    {
        $q = (new \EntryQuery($this->db))->outerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` OUTER JOIN `entries_data_4` AS `t_4` ON `e`.`id` = `t_4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->outerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testRightJoinField()
    {
        $q = (new \EntryQuery($this->db))->rightJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` RIGHT JOIN `entries_data_4` AS `t_4` ON `e`.`id` = `t_4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->rightJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testWhereField()
    {
        $q = (new \EntryQuery($this->db))->whereField(4, ['value' => 4]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `t_4`.`value` = :t_4_value",
            $q->generateSQL(),
            'new EntryQuery with ->whereField() with a single filter'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['t_4_value'], 't_4_value is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSortRand()
    {
        $q = (new \EntryQuery($this->db))->sort('system:id', 'RAND');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` RAND()",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id, RAND)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortCreationDateDesc()
    {
        $q = (new \EntryQuery($this->db))->sort('system:creation-date', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`creation_date_gmt` DESC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:creation-date, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortModificationDate()
    {
        $q = (new \EntryQuery($this->db))->sort('system:modification-date');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`modification_date_gmt` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:modification-date)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortSystemId()
    {
        $q = (new \EntryQuery($this->db))->sort('system:id');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSortSystemId()
    {
        $q = (new \EntryQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSortInvalid()
    {
        $q = (new \EntryQuery($this->db))->sort('<invalid>');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
