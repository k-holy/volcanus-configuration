<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright 2011-2013 k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\Configuration\Tests;

use Volcanus\Configuration\Configuration;

/**
 * Test for Configuration
 *
 * @author k.holy74@gmail.com
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

	public function testConstructor()
	{
		$config = new Configuration(array(
			'foo' => true,
			'bar' => false,
		));
		$this->assertTrue($config['foo']);
		$this->assertFalse($config['bar']);
	}

	public function testConstructorAcceptTraversable()
	{
		$config = new Configuration(new \ArrayIterator(array(
			'foo' => true,
			'bar' => false,
		)));
		$this->assertTrue($config['foo']);
		$this->assertFalse($config['bar']);
	}

	public function testCreateFromJson()
	{
		$config = Configuration::createFromJson(<<<'JSON'
{
    "foo": true,
    "bar": false,
    "arr": ["A", "B", "C"],
    "dict": {
        "a": "A",
        "b": "B",
        "c": "C"
    }
}
JSON
		);
		$this->assertTrue($config['foo']);
		$this->assertFalse($config['bar']);
		$this->assertEquals('A', $config['arr'][0]);
		$this->assertEquals('B', $config['arr'][1]);
		$this->assertEquals('C', $config['arr'][2]);
		$this->assertEquals('A', $config['dict']['a']);
		$this->assertEquals('B', $config['dict']['b']);
		$this->assertEquals('C', $config['dict']['c']);
	}

	public function testDefineAttribute()
	{
		$config = new Configuration();
		$config->define('foo');
		$this->assertNull($config['foo']);
	}

	public function testDefineAttributeWithDefaultValue()
	{
		$config = new Configuration();
		$config->define('foo', true);
		$this->assertTrue($config['foo']);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenDefineAttributeAlreadyExists()
	{
		$config = new Configuration(array(
			'foo' => null,
		));
		$config->define('foo');
	}

	public function testDefineAttributeAsAProperty()
	{
		$config = new Configuration();
		$config->define('attributes', true);
		$this->assertTrue($config['attributes']);
	}

	public function testOffsetSet()
	{
		$config = new Configuration(array(
			'foo' => null,
		));
		$config->offsetSet('foo', true);
		$this->assertTrue($config['foo']);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenSetAttributeNotDefined()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$config->offsetSet('bar', 'A');
	}

	public function testOffsetGet()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$this->assertTrue($config->offsetGet('foo'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenGetAttributeNotDefined()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$config->offsetGet('bar');
	}

	public function testOffsetGetCallableObjectAttribute()
	{
		$config = new Configuration(array(
			'foo' => function() {
				return 'Im Closure';
			},
		));
		$this->assertTrue(is_callable($config->offsetGet('foo')));
		$this->assertTrue(is_object($config->offsetGet('foo')));
		$this->assertEquals('Im Closure', call_user_func($config->offsetGet('foo')));
	}

	public function testOffsetGetCallableStringAttribute()
	{
		$config = new Configuration(array(
			'foo' => 'phpinfo',
		));
		$this->assertTrue(is_callable($config->offsetGet('foo')));
		$this->assertEquals('phpinfo', $config->offsetGet('foo'));
	}

	public function testOffsetGetCallableObjectAttributeWithExcuteCallable()
	{
		$config = new Configuration(array(
			'foo' => 0,
			'bar' => function($config) {
				return $config['foo'] * 2;
			},
		), Configuration::EXECUTE_CALLABLE);
		$this->assertEquals(0, $config->offsetGet('bar'));
		$config['foo'] = 1;
		$this->assertEquals(2, $config->offsetGet('bar'));
		$config['foo'] = 5;
		$this->assertEquals(10, $config->offsetGet('bar'));
	}

	public function testCallMethodCallableObjectAttribute()
	{
		$config = new Configuration(array(
			'foo' => function($name) {
				return sprintf('Im %s', $name);
			},
		));
		$this->assertEquals('Im Argument', $config->foo('Argument'));
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testRaiseExceptionWhenCallMethodAttributeNotCallable()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$config->foo();
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testRaiseExceptionWhenCallMethodAttributeNotDefined()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$config->bar();
	}

	public function testConstructorAcceptAttributeAlreadyDefinedAsAMethod()
	{
		$config = new Configuration(array(
			'offsetGet' => false,
		));
		$this->assertFalse($config['offsetGet']);
		$config->offsetSet('offsetGet', true);
		$this->assertTrue($config['offsetGet']);
	}

	public function testDefineAttributeAlreadyDefinedAsAMethod()
	{
		$config = new Configuration();
		$config->define('offsetGet', false);
		$this->assertFalse($config['offsetGet']);
		$config->offsetSet('offsetGet', true);
		$this->assertTrue($config['offsetGet']);
	}

	public function testConstructorAcceptCallableObjectAttributeAlreadyDefinedAsAMethod()
	{
		$config = new Configuration(array(
			'offsetGet' => function() {
				return false;
			},
		));
		$this->assertFalse(call_user_func($config['offsetGet']));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenConstructorSetCallableObjectAttributeAlreadyDefinedAsAMethodWithExcuteCallable()
	{
		$config = new Configuration(array(
			'offsetGet' => function() {
				return false;
			},
		), Configuration::EXECUTE_CALLABLE);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenDefineCallableObjectAttributeAlreadyDefinedAsAMethodWithExcuteCallable()
	{
		$config = new Configuration(array(), Configuration::EXECUTE_CALLABLE);
		$config->define('offsetGet', function() {
			return false;
		});
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRaiseExceptionWhenSetCallableObjectAttributeAlreadyDefinedAsAMethodWithExcuteCallable()
	{
		$config = new Configuration(array(
			'offsetGet' => null,
		), Configuration::EXECUTE_CALLABLE);
		$config->offsetSet('offsetGet', function() {
			return false;
		});
	}

	public function testToString()
	{
		$config = new Configuration(new \ArrayIterator(array(
			'foo' => true,
			'bar' => false,
		)));
		$this->assertTrue(is_string($config->__toString()));
	}

	public function testToStringCallableAttribute()
	{
		$config = new Configuration(new \ArrayIterator(array(
			'foo' => true,
			'bar' => function() {
				return false;
			},
		)));
		$this->assertTrue(is_string($config->__toString()));
	}

	public function testOffsetUnset()
	{
		$config = new Configuration(array(
			'foo' => true,
		));
		$config->offsetUnset('foo');
		$this->assertNull($config['foo']);
	}

	public function testOffsetExists()
	{
		$config = new Configuration(array(
			'foo' => true,
			'bar' => null,
		));
		$this->assertTrue($config->offsetExists('foo'));
		$this->assertFalse($config->offsetExists('bar'));
		$this->assertFalse($config->offsetExists('baz'));
	}

	public function testArrayAccess()
	{
		$config = new Configuration(array(
			'foo' => null,
			'bar' => null,
		));
		$config['foo'] = true;
		$config['bar'] = false;
		$this->assertTrue($config['foo']);
		$this->assertFalse($config['bar']);
	}

	public function testPropertyAccess()
	{
		$config = new Configuration(array(
			'foo' => null,
			'bar' => null,
		));
		$config->foo = true;
		$config->bar = false;
		$this->assertTrue($config->foo);
		$this->assertFalse($config->bar);
	}

	public function testIssetArrayAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
			'bar' => null,
		));
		$this->assertTrue(isset($config['foo']));
		$this->assertFalse(isset($config['bar']));
		$this->assertFalse(isset($config['baz']));
	}

	public function testIssetPropertyAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
			'bar' => null,
		));
		$this->assertTrue(isset($config->foo));
		$this->assertFalse(isset($config->bar));
		$this->assertFalse(isset($config->baz));
	}

	public function testUnsetArrayAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
		));
		$this->assertTrue(isset($config['foo']));
		unset($config['foo']);
		$this->assertFalse(isset($config['foo']));
	}

	public function testUnsetPropertyAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
		));
		$this->assertTrue(isset($config->foo));
		unset($config->foo);
		$this->assertFalse(isset($config->foo));
	}

	public function testIsNullArrayAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
			'bar' => null,
		));
		$this->assertFalse(is_null($config['foo']));
		$this->assertTrue(is_null($config['bar']));
	}

	public function testIsNullPropertyAccess()
	{
		$config = new Configuration(array(
			'foo' => false,
			'bar' => null,
		));
		$this->assertFalse(is_null($config->foo));
		$this->assertTrue(is_null($config->bar));
	}

	public function testRecursiveArrayAccess()
	{
		$config = new Configuration(array(
			'array' => array('a' => 'A', 'b' => 'B', 'c' => 'C'),
			'object' => new \ArrayObject(array(
				'a' => new \ArrayObject(array(
					'a' => array('a' => 'A', 'b' => 'B', 'c' => array('a' => 'A', 'b' => 'B', 'c'=> 'C')),
					'callable' => function() {
						return 'Im Closure';
					},
				)),
			)),
		), Configuration::EXECUTE_CALLABLE);
		$this->assertEquals('A', $config['array']['a']);
		$this->assertEquals('B', $config['array']['b']);
		$this->assertEquals('C', $config['array']['c']);
		$this->assertEquals('A', $config['object']['a']['a']['a']);
		$this->assertEquals('B', $config['object']['a']['a']['b']);
		$this->assertEquals('A', $config['object']['a']['a']['c']['a']);
		$this->assertEquals('B', $config['object']['a']['a']['c']['b']);
		$this->assertEquals('C', $config['object']['a']['a']['c']['c']);
		$this->assertEquals('Im Closure', $config['object']['a']['callable']);
	}

	public function testRecursivePropertyAccess()
	{
		$config = new Configuration(array(
			'array' => array('a' => 'A', 'b' => 'B', 'c' => 'C'),
			'object' => new \ArrayObject(array(
				'a' => new \ArrayObject(array(
					'a' => array('a' => 'A', 'b' => 'B', 'c' => array('a' => 'A', 'b' => 'B', 'c'=> 'C')),
					'callable' => function() {
						return 'Im Closure';
					},
				)),
			)),
		), Configuration::EXECUTE_CALLABLE);
		$this->assertEquals('A', $config->array->a);
		$this->assertEquals('B', $config->array->b);
		$this->assertEquals('C', $config->array->c);
		$this->assertEquals('A', $config->object->a->a->a);
		$this->assertEquals('B', $config->object->a->a->b);
		$this->assertEquals('A', $config->object->a->a->c->a);
		$this->assertEquals('B', $config->object->a->a->c->b);
		$this->assertEquals('C', $config->object->a->a->c->c);
		$this->assertEquals('Im Closure', $config->object->a->callable);
	}

	public function testImplementsTraversable()
	{
		$config = new Configuration(array(
			'foo' => true,
			'bar' => false,
		));
		$this->assertInstanceOf('\Traversable', $config);
		foreach ($config as $name => $value) {
			switch ($name) {
			case 'foo':
				$this->assertTrue($value);
				break;
			case 'bar':
				$this->assertFalse($value);
				break;
			}
		}
	}

	public function testImplementsCountable()
	{
		$config = new Configuration(array(
			'foo' => true,
			'bar' => false,
		));
		$this->assertInstanceOf('\Countable', $config);
		$this->assertCount(2, $config);
	}

	public function testToArray()
	{
		$config = new Configuration(array(
			'array' => array('a' => 'A', 'b' => 'B', 'c' => 'C'),
			'object' => new \ArrayObject(array(
				'a' => new \ArrayObject(array(
					'a' => array('a' => 'A', 'b' => 'B', 'c' => array('a' => 'A', 'b' => 'B', 'c'=> 'C')),
					'callable' => function() {
						return 'Im Closure';
					},
				)),
			)),
		), Configuration::EXECUTE_CALLABLE);
		$this->assertEquals(array(
			'array' => array('a' => 'A', 'b' => 'B', 'c' => 'C'),
			'object' => array(
				'a' => array(
					'a' => array('a' => 'A', 'b' => 'B', 'c' => array('a' => 'A', 'b' => 'B', 'c'=> 'C')),
					'callable' => 'Im Closure',
				),
			),
		), $config->toArray());
	}

}
