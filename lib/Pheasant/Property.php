<?php

namespace Pheasant;

/**
 * A property represents a scalar value associated with a domain object
 */
class Property
{
	public $name, $type, $futures=array();

	/**
	 * Constructor
	 */
	public function __construct($name, $type)
	{
		$this->name = $name;
		$this->type = $type;
	}

	/**
	 * Returns the name of the property
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Returns the default value for a property, or NULL
	 */
	public function defaultValue()
	{
		return isset($this->type->options->default)
			? $this->type->options->default
			: NULL
			;
	}

	/**
	 * Return a closure for accessing the value of the property
	 * @return closure
	 */
	public function getter($key)
	{
		$property = $this;
		return function($object) use($key, $property) {
			return is_null($object->get($key))
				? $property->future($object)
				: $object->get($key)
				;
		};
	}

	/**
	 * Return a closure that when called sets the value of the property
	 * @return closure
	 */
	public function setter($key)
	{
		return function($object, $value) use($key) {
			return $object->set($key, $value);
		};
	}

	/**
	 * Returns a future reference to the property value
	 */
	public function future($object)
	{
		return $this->futures[] = new Future($this, $object);
	}
}