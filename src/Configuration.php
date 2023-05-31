<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright k-holy <k.holy74@gmail.com>
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
    private int $executeCallable;

    /**
     * @var array 属性の配列
     */
    private array $attributes;

    /**
     * コンストラクタ
     *
     * @param iterable $attributes 属性の配列
     * @param int $executeCallable 属性値がcallableの場合に実行結果を返すかどうか
     */
    public function __construct(iterable $attributes = [], int $executeCallable = self::NOT_EXECUTE_CALLABLE)
    {
        $this->executeCallable = $executeCallable;
        $this->initialize($attributes);
    }

    /**
     * 属性を初期化します。
     * 引数が指定されている場合は属性値を再帰的にセットします。
     * 要素が配列またはTraversable実装オブジェクトの場合、
     * ラッピングすることで配列アクセスとプロパティアクセスを提供します。
     *
     * @param iterable $attributes 属性の配列
     * @return static
     * @throws \InvalidArgumentException
     */
    public function initialize(iterable $attributes = []): static
    {
        $this->attributes = [];
        foreach ($attributes as $name => $value) {
            $this->define($name, $value);
        }
        return $this;
    }

    /**
     * JSON文字列を元にオブジェクトを生成して返します。
     *
     * @param string $json JSON文字列
     * @param int $executeCallable 属性値がcallableの場合に実行結果を返すかどうか
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function createFromJson(string $json, int $executeCallable = self::NOT_EXECUTE_CALLABLE): static
    {
        $attributes = json_decode($json, true);
        if ($attributes === null) {
            $message = 'Unknown error.';
            if (function_exists('json_last_error_msg')) {
                $message = json_last_error_msg();
            } else {
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $message = 'Maximum stack depth exceeded.';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $message = 'Underflow or the modes mismatch.';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $message = 'Unexpected control character found.';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $message = 'Syntax error, malformed JSON.';
                        break;
                    case JSON_ERROR_UTF8:
                        $message = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                        break;
                    default:
                        break;
                }
            }
            throw new \InvalidArgumentException(sprintf('JSON parse error: %s', $message));
        }
        return new static($attributes, $executeCallable);
    }

    /**
     * 属性名および初期値をセットします。
     *
     * @param string $name 属性名
     * @param mixed|null $value 初期値
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function define(string $name, mixed $value = null): static
    {
        if (array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" already exists.', $name));
        }
        if ($this->executeCallable === self::EXECUTE_CALLABLE &&
            $value instanceof \Closure &&
            method_exists($this, $name)
        ) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" is already defined as a method.', $name)
            );
        }
        if (is_array($value) || $value instanceof \Traversable) {
            $value = new static($value, $this->executeCallable);
        }
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * __isset
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return (array_key_exists($name, $this->attributes) && $this->attributes[$name] !== null);
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get(string $name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" does not exists.', $name));
        }
        if ($this->executeCallable === self::EXECUTE_CALLABLE &&
            $this->attributes[$name] instanceof \Closure
        ) {
            return $this->attributes[$name]($this);
        }
        return $this->attributes[$name];
    }

    /**
     * __set
     *
     * @param string $name
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function __set(string $name, mixed $value)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" does not exists.', $name));
        }
        if ($this->executeCallable === self::EXECUTE_CALLABLE &&
            $value instanceof \Closure &&
            method_exists($this, $name)
        ) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" is already defined as a method.', $name)
            );
        }
        if (is_array($value) || $value instanceof \Traversable) {
            $value = new static($value, $this->executeCallable);
        }
        $this->attributes[$name] = $value;
    }

    /**
     * __unset
     *
     * @param string $name
     */
    public function __unset(string $name)
    {
        if (array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = null;
        }
    }

    /**
     * ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

    /**
     * magic call method
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        if (array_key_exists($name, $this->attributes) && $this->attributes[$name] instanceof \Closure) {
            return call_user_func_array($this->attributes[$name], $args);
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
        return (string)var_export($this->toArray(), true);
    }

    /**
     * IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Countable::count()
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * 配列に変換して返します。
     *
     * @return array
     */
    public function toArray(): array
    {
        $values = [];
        foreach ($this->attributes as $name => $value) {
            $value = $this->__get($name);
            $values[$name] = ($value instanceof self)
                ? $value->toArray()
                : $value;
        }
        ksort($values);
        return $values;
    }

}
