<?php

namespace Pheasant\Database\Sqlite;

use \Pheasant\Query\Criteria;

/**
 * An Sqlite table
 */
class Table
{
	private $_name, $_connection, $_columns;

	/**
	 * Constructor
	 */
	public function __construct($name, $connection)
	{
		$this->_name = $name;
		$this->_connection = $connection;
	}

	/**
	 * Creates the table, fails if the table exists
	 * @param $columns a map defining columns to Type objects
	 */
	public function create($columns, $options='')
	{
		$types = new TypeMap($columns);
		$sql = sprintf(
			'CREATE TABLE `%s` (%s) %s',
			$this->_name,
			implode(', ', $types->columnDefs()),
			$options
		);

		$this->_connection->execute($sql);
	}

	/**
	 * Creates the table if it doesn't exist
	 */
	public function createIfNotExists($columns, $options='')
	{
		if(!$this->exists())
			$this->create($columns, $options);
	}

	/**
	 * Drops the table
	 * @chainable
	 */
	public function drop()
	{
		$this->_connection->execute(sprintf('DROP TABLE `%s`',$this->_name));
		return $this;
	}

	/**
	 * Truncates the table
	 * @chainable
	 */
	public function truncate()
	{
		$this->_connection->execute(sprintf('TRUNCATE TABLE `%s`',$this->_name));
		return $this;
	}

	/**
	 * Determines if the table exists (name only, column definition not checked)
	 */
	public function exists()
	{
		return (bool) $this->_connection->execute(
			"SELECT name FROM sqlite_master WHERE type='table' AND name=?",
			$this->_name
			)->fetchOne();
	}

	/**
	 * Returns all the database columns
	 */
	public function columns()
	{
		if(!isset($this->_columns))
		{
			$this->_columns = array();

			foreach($this->_connection->execute("SHOW COLUMNS FROM `{$this->_name}`") as $c)
			{
				$column = $c['Field'];
				unset($c['Field']);
				$this->_columns[$column] = $c;
			}
		}
	
		return $this->_columns;
	}	

	/**
	 * Inserts a row into the table
	 */
	public function insert($data)
	{
		if(empty($data))
			throw new Exception("Can't insert an empty row");

		return $this->_connection->execute(sprintf(
			'INSERT INTO `%s` (%s) VALUES (%s)',
			$this->_name,
			implode(',', array_keys($data)),
			implode(',', array_fill(0, count($data), '?'))
			), array_values($data)
		);
	}

	/**
	 * Updates a row into the table
	 */
	public function update($data, Criteria $where)
	{
		if(empty($data))
			throw new Exception("Can't insert an empty row");

		return $this->_connection->execute(sprintf(
			'UPDATE `%s` SET %s WHERE %s',
			$this->_name,
			$this->_buildSet($data),
			$where
			), array_values($data)
		);
	}

	/**
	 * Tries to update a record, or inserts if it doesn't exist
	 */
	public function upsert($data)
	{
		if(empty($data))
			throw new Exception("Can't insert an empty row");

		var_dump('upsert!');
		var_dump($data);

		return $this->insert($data);	

		return $this->_connection->execute(sprintf(
			'INSERT INTO `%s` SET %2$s ON DUPLICATE KEY UPDATE %2$s',
			$this->_name,
			$this->_buildSet($data)
			), array_merge(array_values($data),array_values($data))
		);
	}

	/**
	 * Inserts a row, or replaces it entirely if it exists 
	 */
	public function replace($data)
	{
		if(empty($data))
			throw new Exception("Can't replace an empty row");

		return $this->_connection->execute(sprintf(
			'REPLACE INTO `%s` SET %s',
			$this->_name,
			$this->_buildSet($data)
			), array_values($data)
		);
	}	

	/**
	 * Builds a series of X=?, Y=?, Z=?
	 */
	private function _buildSet($data)
	{
		$columns = '';

		foreach($data as $key=>$value)
			$columns[] = sprintf('`%s`=?',$key);

		return implode(', ', $columns);
	}
}

