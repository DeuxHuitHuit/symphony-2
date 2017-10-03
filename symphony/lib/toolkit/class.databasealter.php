<?php

/**
 * @package toolkit
 */

/**
 * This DatabaseStatement specialization class allows creation of ALTER TABLE statements.
 */
final class DatabaseAlter extends DatabaseStatement
{
    /**
     * The default collate option value for this statement
     *
     * @var string
     */
    private $collate;

    /**
     * Creates a new DatabaseAlter statement on table $table, with an optional
     * optimizer value.
     *
     * @see Database::alter()
     * @param Database $db
     *  The underlying database connection
     * @param string $table
     *  The name of the table to act on.
     */
    public function __construct(Database $db, $table)
    {
        parent::__construct($db, 'ALTER TABLE');
        $table = $this->replaceTablePrefix($table);
        $table = $this->asTickedString($table);
        $this->unsafeAppendSQLPart('table', $table);
    }

    /**
     * Set the default collate for all textual columns being altered.
     *
     * @param string $collate
     *  The collate to use by default
     * @return DatabaseAlter
     *  The current instance
     */
    public function collate($collate)
    {
        $this->collate = $collate;
        return $this;
    }

    /**
     * This method checks if the $key index is not empty in the $options array.
     * If it is not empty, it will return its value. If is it, it will lookup a
     * member variable on the current instance.
     *
     * @see DatabaseStatement::getOption()
     * @param array $options
     * @param string|int $key.
     * @return mixed
     */
    protected function getOption(array $options, $key)
    {
        return !empty($options[$key]) ? $options[$key] : !empty($this->{$key}) ? $this->{$key} : null;
    }

    /**
     * Appends the FIRST keyword
     *
     * @return DatabaseAlter
     *  The current instance
     */
    public function first()
    {
        $this->unsafeAppendSQLPart('first', "FIRST");
        return $this;
    }

    /**
     * Appends a AFTER `column` clause
     *
     * @param string|array $column
     *  The column to use with the AFTER keyword
     * @return DatabaseAlter
     *  The current instance
     */
    public function after($column)
    {
        General::ensureType([
            'column' => ['var' => $column, 'type' => 'string'],
        ]);
        $column = $this->asTickedString($column);
        $this->unsafeAppendSQLPart('after', "AFTER $column");
        return $this;
    }

     /**
     * Appends multiple ADD COLUMN `column` clause.
     *
     * @see DatabaseStatement::buildColumnDefinitionFromArray()
     * @param string|array $column
     *  The column to use with the AFTER keyword
     * @return DatabaseAlter
     *  The current instance
     */
    public function add(array $columns)
    {
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $column = $this->buildColumnDefinitionFromArray($k, $column);
            return "ADD COLUMN $column";
        }, $columns));
        $this->unsafeAppendSQLPart('add columns', $columns);
        return $this;
    }

    /**
     * Appends one or multiple DROP COLUMN `column` clause.
     *
     * @param array|string $columns
     *  Array of columns names
     * @return DatabaseAlter
     *  The current instance
     */
    public function drop($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $columns = implode(self::LIST_DELIMITER, array_map(function ($column) {
            $column = $this->asTickedString($column);
            return "DROP COLUMN $column";
        }, $columns));
        $this->unsafeAppendSQLPart('drop columns', $columns);
        return $this;
    }

    /**
     * Appends a CHANGE COLUMN `old_column` `new_column` clause.
     *
     * @see DatabaseStatement::buildColumnDefinitionFromArray()
     * @param array|string $old_columns
     *  The name of the old columns to change. Their new version must be specified at the same
     *  index in $new_columns
     * @param array $new_columns
     *  The new columns definitions
     * @return DatabaseAlter
     *  The current instance
     */
    public function change($old_columns, array $new_columns)
    {
        if (!is_array($old_columns)) {
            $old_columns = [$old_columns];
        }
        $new_columns_keys = array_keys($new_columns);
        $columns = implode(self::LIST_DELIMITER, General::array_map(function ($index, $column) use ($new_columns_keys, $new_columns) {
            $old_column = $this->asTickedString($column);
            $new_column = $this->buildColumnDefinitionFromArray(
                $new_columns_keys[$index],
                $new_columns[$new_columns_keys[$index]]
            );
            return "CHANGE COLUMN $old_column $new_column";
        }, $old_columns));
        $this->unsafeAppendSQLPart('change columns', $columns);
        return $this;
    }

    /**
     * Appends one or multiple ADD KEY `key` clause.
     *
     * @see DatabaseStatement::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  The key definitions to append
     * @return DatabaseAlter
     *  The current instance
     */
    public function addKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'key'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add key', $keys);
        return $this;
    }

    /**
     * Appends one or multiple DROP KEY `key` clause.
     *
     * @param array|string $keys
     *  The key definitions to drop
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP KEY $key";
        }, $keys));
        $this->unsafeAppendSQLPart('drop key', $keys);
        return $this;
    }

    /**
     * Appends one or multiple ADD INDEX `index` clause.
     *
     * @see DatabaseStatement::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  The index definitions to append
     * @return DatabaseAlter
     *  The current instance
     */
    public function addIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'index'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add index', $keys);
        return $this;
    }

    /**
     * Appends one or multiple DROP INDEX `index` clause.
     *
     * @param array|string $keys
     *  The index definitions to drop
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropIndex($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $keys = implode(self::LIST_DELIMITER, array_map(function ($key) {
            $key = $this->asTickedString($key);
            return "DROP INDEX $key";
        }, $keys));
        $this->unsafeAppendSQLPart('drop index', $keys);
        return $this;
    }

     /**
     * Appends one and only one ADD PRIMARY KEY `key` clause.
     *
     * @see DatabaseStatement::buildKeyDefinitionFromArray()
     * @param array|string $keys
     *  One or more columns inclued in the primary key
     * @return DatabaseAlter
     *  The current instance
     */
    public function addPrimaryKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys => 'primary'];
        }
        $keys = implode(self::LIST_DELIMITER, General::array_map(function ($k, $column) {
            $key = $this->buildKeyDefinitionFromArray($k, $column);
            return "ADD $key";
        }, $keys));
        $this->unsafeAppendSQLPart('add primary key', $keys);
        return $this;
    }

    /**
     * Appends one and only one DROP PRIMARY KEY clause.
     *
     * @return DatabaseAlter
     *  The current instance
     */
    public function dropPrimaryKey()
    {
        $this->unsafeAppendSQLPart('drop primary key', 'DROP PRIMARY KEY');
        return $this;
    }
}
