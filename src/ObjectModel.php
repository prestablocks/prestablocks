<?php
/**
 * Based on https://github.com/amaurigabriel/PrestaShopCustomObjectModel
 *
 * @author Pablo Largo Mohedano <pablolargo@ecomputer.es>
 */

namespace Devnix\Prestablocks;

use \Db;
use \Tools;

if (!defined('_PS_VERSION_'))
    exit;

class ObjectModel extends \ObjectModel
{
    /**
     * return a array with the columns that exists in the
     * table relative to the ObjectModel
     */
    public function getDatabaseColumns()
    {
        $definition = ObjectModel::getDefinition($this);

        $sql = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA="' . _DB_NAME_ . '" AND TABLE_NAME="' . _DB_PREFIX_ . $definition['table'] . '"';

        return Db::getInstance()->executeS($sql);
    }

    /*
     * return a column in the tablerelative to the ObjectModel.
     * this method uses the $definition property of the ObjectModel,
     * with some extra properties.
     *
     * Example:
     * 'table'        => 'tablename',
     * 'primary'      => 'id',
     * 'fields'       => [
     *     'id'     => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
     *     'number' => [
     *         'type'     => self::TYPE_STRING,
     *         '@type'  => 'varchar(20)',
     *         'required' => true,
     *         'default'  => '25'
     *     ],
     * ],
     *
     * The primary column is created automatically as INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT. The other columns
     * require an extra parameter, with the type of the column in the database.
     *
     *
     */
    public function createColumn($name, $column_definition)
    {
        $definition = ObjectModel::getDefinition($this);

        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . $definition['table'];
        $sql .= ' ADD COLUMN ' . $name . ' ' . $column_definition['@type'];

        if ($field_name === $definition['primary'])
        {
            $sql .= ' INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT';
        }
        else
        {
            if (isset($field['required']) && $field['required'])
            {
                $sql .= ' NOT NULL';
            }

            if (isset($field['default']))
            {
                $sql .= ' DEFAULT "' . $field['default'] . '"';
            }
        }

        if (!Db::getInstance()->execute($sql)) {
            Tools::displayError(Db::getInstance()->getLink()->errorInfo()[2]);
            return false;
        } else {
            return true;
        }
    }

    /**
     *  Create in the database every column detailed in the $definition property that are
     *  missing in the database.
     */
    public function createMissingColumns()
    {
        $columns    = $this->getDatabaseColumns();
        $definition = ObjectModel::getDefinition($this);

        foreach ($definition['fields'] as $column_name => $column_definition)
        {
            //column exists in database
            $exists = false;

            foreach ($columns as $column)
            {
                if ($column['COLUMN_NAME'] === $column_name)
                {
                    $exists = true;
                }
            }

            if (!$exists) {
                $this->createColumn($column_name, $column_definition);
            }
        }

        return true;
    }

    /**
     *  Create the database table with its columns. Similar to the createColumn() method.
     */
    public function createDatabase()
    {
        $definition = ObjectModel::getDefinition($this);

        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . $definition['table'] . ' (';
        $sql .= $definition['primary'] . ' INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,';

        foreach ($definition['fields'] as $field_name => $field)
        {
            if ($field_name === $definition['primary'])
            {
                continue;
            }

            $sql .= $field_name . ' ' . $field['@type'];

            if (isset($field['required']) && $field['required'])
            {
                $sql .= ' NOT NULL';
            }

            if (isset($field['default']))
            {
                $sql .= ' DEFAULT "' . $field['default'] . '"';
            }

            $sql .= ',';
        }

        $sql = trim($sql, ',');
        $sql .= ')';

        if (!Db::getInstance()->execute($sql)) {
            throw new \PrestaShopDatabaseException(Db::getInstance()->getLink()->errorInfo()[2]);
            return false;
        } else {
            return true;
        }
    }

    public function removeDatabase() {
        $definition = ObjectModel::getDefinition($this);

        if (Db::getInstance()->getValue('SELECT count(*) FROM information_schema.tables WHERE TABLE_SCHEMA LIKE \''._DB_NAME_.'\' AND TABLE_NAME = \''._DB_PREFIX_.$definition['table'].'\'') > 0) {
          $sql = 'DROP TABLE '. _DB_PREFIX_ . $definition['table'];

          if (!Db::getInstance()->execute($sql)) {
            Tools::displayError(Db::getInstance()->getLink()->errorInfo()[2]);
            return false;
          } else {
            return true;
          }
        }

        return true;
    }
}
