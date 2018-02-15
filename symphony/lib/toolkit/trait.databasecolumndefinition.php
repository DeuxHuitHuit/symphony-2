<?php

/**
 * @package toolkit
 */

trait DatabaseColumnDefinition
{
    /**
     * @internal
     * Given a field name valid field $k, this methods build a column definition
     * SQL part from an array of options. It will use the array $options to generate
     * the a complete SQL definition part, with all its possible properties.
     *
     * This method is mostly used for CREATE and ALTER statements.
     *
     * @see validateFieldName()
     * @see getOptions()
     * @see DatabaseCreate
     * @see DatabaseAlter
     * @param string $k
     *  The name of the field
     * @param string|array $options
     *  All the options needed to properly create the column.
     *  The method `getOptions()` is used to get the value of the field.
     *  When the value is a string, it is considered as the column's type.
     * @param string $options.type
     *  The SQL type of the column.
     * @param string $options.collate
     *  The collate to use with this column. Only used for character based columns.
     * @param bool $options.null
     *  If the column should accept NULL. Defaults to false, i.e. NOT NULL.
     * @param string|int $options.default
     *  The default value of the column.
     * @param bool $options.signed
     *  If the column should be signed. Only used for number based columns.
     *  Defaults to false, i.e. UNSIGNED.
     * @param bool $options.auto
     *  If the column should use AUTO_INCREMENT. Only used for integer based columns.
     *  Defaults to false.
     * @return string
     *  The SQL part containing the column definition.
     * @throws DatabaseException
     */
    public function buildColumnDefinitionFromArray($k, $options)
    {
        if (is_string($options)) {
            $options = ['type' => $options];
        } elseif (!is_array($options)) {
            throw new DatabaseException('Field value can only be a string or an array');
        } elseif (!isset($options['type'])) {
            throw new DatabaseException('Field type must be defined.');
        }
        $type = strtolower($options['type']);
        $collate = $this->getOption($options, 'collate');
        if ($collate) {
            $collate = ' COLLATE ' . $collate;
        }
        $notNull = !isset($options['null']) || $options['null'] === false;
        $null = $notNull ? ' NOT NULL' : ' DEFAULT NULL';
        $default = $notNull && isset($options['default']) ?
            " DEFAULT " . $this->getDb()->quote($options['default']) :
            '';
        $unsigned = !isset($options['signed']) || $options['signed'] === false;
        $stringOptions = $collate . $null . $default;

        if (strpos($type, 'varchar') === 0 || strpos($type, 'text') === 0) {
            $type .= $stringOptions;
        } elseif (strpos($type, 'enum') === 0) {
            if (isset($options['values']) && is_array($options['values'])) {
                $type .= "(" . implode(
                    self::LIST_DELIMITER,
                    array_map([$this->getDb(), 'quote'], $options['values'])
                ) . ")";
            }
            $type .= $stringOptions;
        } elseif (strpos($type, 'int') === 0) {
            if ($unsigned) {
                $type .= ' unsigned';
            }
            $type .= $null . $default;
            if (isset($options['auto']) && $options['auto']) {
                $type .= ' AUTO_INCREMENT';
            }
        } elseif (strpos($type, 'datetime') === 0) {
            $type .= $null . $default;
        }
        $k = $this->asTickedString($k);
        return "$k $type";
    }
}
