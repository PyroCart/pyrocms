<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2012, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 2.1.0
 * @filesource
 */

/**
 * PDO Forge Class
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/database/
 */
class CI_DB_pdo_forge extends CI_DB_forge {

	protected $_drop_database	= 'DROP DATABASE %s';

	/**
	 * Drop Table
	 *
	 * @param	string	the table name
	 * @param	bool	should 'IF NOT EXISTS' be added to the SQL
	 * @return	bool
	 */
	protected function _drop_table($table, $if_exists = false)
	{
		$sql = 'DROP TABLE ';

		if ($if_exists === TRUE)
		{
			$sql .= 'IF EXISTS ';
		}

		return $sql . $this->db->escape_identifiers($table);
	}

	/**
	 * Create Table
	 *
	 * @param	string	the table name
	 * @param	array	the fields
	 * @param	mixed	primary key(s)
	 * @param	mixed	key(s)
	 * @param	bool	should 'IF NOT EXISTS' be added to the SQL
	 * @return	bool
	 */
	protected function _create_table($table, $fields, $primary_keys, $keys, $if_not_exists)
	{
		$sql = 'CREATE TABLE ';

		if ($if_not_exists === TRUE)
		{
			$sql .= 'IF NOT EXISTS ';
		}

		$sql .= $this->db->escape_identifiers($table).' (';

		$sql .= $this->_process_fields($fields);

		if (count($primary_keys) > 0)
		{
			$sql .= ",\n\tPRIMARY KEY (".implode(', ', $this->db->escape_identifiers($primary_keys)).')';
		}

		if (is_array($keys) && count($keys) > 0)
		{
			foreach ($keys as $key)
			{
				if (is_array($key))
				{
					$key_name = $this->db->escape_identifiers(implode('_', $key));
					$key = $this->db->escape_identifiers($key);
				}
				else
				{
					$key_name = $this->db->escape_identifiers($key);
					$key = array($key_name);
				}

				$sql .= ",\n\tKEY ".$key_name.' ('.implode(', ', $key).')';
			}
		}

		return $sql."\n)";
	}

	protected function _process_fields(array $fields)
	{
		$current_field_count = 0;
		$sql = '';

		foreach ($fields as $field => $attributes)
		{
			// Numeric field names aren't allowed in databases, so if the key is
			// numeric, we know it was assigned by PHP and the developer manually
			// entered the field information, so we'll simply add it to the list
			if (is_numeric($field))
			{
				$sql .= "\n\t".$attributes;
			}
			else
			{
				$attributes = array_change_key_case($attributes, CASE_UPPER);
				$numeric = array('SERIAL', 'INTEGER');

				empty($attributes['NAME']) OR $sql .= ' '.$this->db->escape_identifiers($attributes['NAME']).' ';

				$sql .= "\n\t".$this->db->escape_identifiers($field).' '.$attributes['TYPE'];

				if ( ! empty($attributes['CONSTRAINT']))
				{
					if ($this->db->subdriver === 'mysql')
					{
						switch (strtolower($attributes['TYPE']))
						{
							case 'decimal':
							case 'float':
							case 'numeric':
								$sql .= '('.implode(',', $attributes['CONSTRAINT']).')';
								break;
							case 'enum':
							case 'set':
								$sql .= '("'.implode('","', $attributes['CONSTRAINT']).'")';
								break;
							default:
								$sql .= '('.$attributes['CONSTRAINT'].')';
						}
					}
					// Exception for Postgre numeric which not too happy with constraint within those type
					elseif ( ! ($this->db->subdriver === 'pgsql' && in_array($attributes['TYPE'], $numeric)))
					{
						$sql .= '('.$attributes['CONSTRAINT'].')';
					}
				}

				if ( ! empty($attributes['UNSIGNED']) && $attributes['UNSIGNED'] === TRUE)
				{
					$sql .= ' UNSIGNED';
				}

				if (isset($attributes['DEFAULT']))
				{
					$sql .= " DEFAULT '".$attributes['DEFAULT']."'";
				}

				$sql .= ( ! empty($attributes['NULL']) && $attributes['NULL'] === TRUE)
					? ' NULL' : ' NOT NULL';

				if ( ! empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === TRUE)
				{
					$sql .= ' AUTO_INCREMENT';
				}
			}

			// don't add a comma on the end of the last field
			if (++$current_field_count < count($fields))
			{
				$sql .= ',';
			}
		}
		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	 * Alter table query
	 *
	 * Generates a platform-specific query so that a table can be altered
	 * Called by add_column(), drop_column(), and column_alter(),
	 *
	 * @param	string	the ALTER type (ADD, DROP, CHANGE)
	 * @param	string	the column name
	 * @param	array	fields
	 * @param	string	the field after which we should add the new field
	 * @return	string
	 */
	protected function _alter_table($alter_type, $table, $fields, $after_field = '')
	{
		$sql = 'ALTER TABLE '.$this->db->escape_identifiers($table).' '.$alter_type.' ';

		// DROP has everything it needs now.
		if ($alter_type === 'DROP')
		{
			return $sql.$this->db->escape_identifiers($fields);
		}

		return $sql.$this->_process_fields($fields)
			.($after_field !== '' ? ' AFTER '.$this->db->escape_identifiers($after_field) : '');
	}

}

/* End of file pdo_forge.php */
/* Location: ./system/database/drivers/pdo/pdo_forge.php */