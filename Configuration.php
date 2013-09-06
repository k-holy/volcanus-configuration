<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright 2011-2013 k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\Configuration;

/**
 * Configuration
 *
 * @author k.holy74@gmail.com
 */
class Configuration implements \ArrayAccess, \IteratorAggregate, \Countable
{

	const NOT_EXECUTE_CALLABLE = 0;
	const EXECUTE_CALLABLE = 1;

	/**
	 * @var int 属性値がcallableの場合に実行結果を返すかどうか
	 */
	private $executeCallable;

	/**
	 * @var array 属性の配列
	 */
	private $attributes;

	/**
	 * コンストラクタ
	 *
	 * @param array 属性の配列
	 * @param int 属性値がcallableの場合に実行結果を返すかどうか
	 */
	public function __construct($attributes = array(), $executeCallable = self::NOT_EXECUTE_CALLABLE)
	{
		$this->executeCallable = $executeCallable;
		$this->initialize($attributes);
	}

	/**
	 * 属性を初期化します。
	 *
	 * @param array 属性の配列
	 * @return $this
	 */
	public function initialize($attributes = array())
	{
		if (!is_array($attributes) && !($attributes instanceof \Traversable)) {
			throw new \InvalidArgumentException(
				sprintf('The attributes is not Array and not Traversable. type:"%s"',
					(is_object($attributes)) ? get_class($attributes) : gettype($attributes)
				)
			);
		}
		$this->attributes = (!empty($attributes)) ? $this->import($attributes) : array();
		return $this;
	}

	/**
	 * 属性名および初期値をセットします。
	 *
	 * @param string 属性名
	 * @param mixed 初期値
	 * @return $this
	 */
	public function define($name, $value = null)
	{
		if (array_key_exists($name, $this->attributes)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" already exists.', $name));
		}
		if (method_exists($this, $name)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" is already defined as a method.', $name)
			);
		}
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * ArrayAccess::offsetSet()
	 *
	 * @param mixed
	 * @param mixed
	 */
	public function offsetSet($offset, $value)
	{
		if (!array_key_exists($offset, $this->attributes)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" does not exists.', $offset));
		}
		$this->attributes[$offset] = $value;
	}

	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param mixed
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if (!array_key_exists($offset, $this->attributes)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" does not exists.', $offset));
		}
		return (is_callable($this->attributes[$offset]) &&
			$this->executeCallable === self::EXECUTE_CALLABLE)
				? $this->attributes[$offset]($this)
				: $this->attributes[$offset];
	}

	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @param mixed
	 */
	public function offsetUnset($offset)
	{
		if (array_key_exists($offset, $this->attributes)) {
			$this->attributes[$offset] = null;
		}
	}

	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param mixed
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->attributes[$offset]);
	}

	/**
	 * magic setter
	 *
	 * @param string 属性名
	 * @param mixed 属性値
	 */
	public function __set($name, $value)
	{
		$this->offsetSet($name, $value);
	}

	/**
	 * magic getter
	 *
	 * @param string 属性名
	 */
	public function __get($name)
	{
		return $this->offsetGet($name);
	}

	/**
	 * magic call method
	 *
	 * @param string
	 * @param array
	 */
	public function __call($name, $args)
	{
		if (array_key_exists($name, $this->attributes)) {
			$value = $this->attributes[$name];
			if (is_callable($value)) {
				return call_user_func_array($value, $args);
			}
		}
		throw new \BadMethodCallException(
			sprintf('Undefined Method "%s" called.', $name)
		);
	}

	/**
	 * __toString
	 */
	public function __toString()
	{
		return var_export($this->toArray(), true);
	}

	/**
	 * IteratorAggregate::getIterator()
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->attributes);
	}

	/**
	 * Countable::count()
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->attributes);
	}

	/**
	 * 配列に変換して返します。
	 *
	 * @return array
	 */
	public function toArray()
	{
		$values = array();
		foreach ($this->attributes as $name => $value) {
			$value = $this->offsetGet($name);
			$values[$name] = ($value instanceof self)
				? $value->toArray()
				: $value;
		}
		return $values;
	}

	/**
	 * 属性値を配列から再帰的にセットします。
	 * 要素が配列またはTraversable実装オブジェクトの場合、
	 * ラッピングすることで配列アクセスとプロパティアクセスを提供します。
	 *
	 * @param array 属性の配列
	 * @return array
	 */
	private function import($attributes)
	{
		foreach ($attributes as $name => $value) {
			if (method_exists($this, $name)) {
				throw new \InvalidArgumentException(
					sprintf('The attribute "%s" is already defined as a method.', $name)
				);
			}
			$attributes[$name] = (is_array($value) || $value instanceof \Traversable)
				? new static($value, $this->executeCallable)
				: $value
			;
		}
		return $attributes;
	}

}
