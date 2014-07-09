<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */
require_once __DIR__ . '/../bootstrap.php';

use Volcanus\Configuration\Configuration;

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_CALLBACK, function ($file, $line) {
    echo '<pre>' . htmlspecialchars(sprintf("Assertion Failed: at %s[%d]\n", $file, $line)) . '</pre>';
});


$config = new Configuration();

echo '<p>define()メソッドで項目を定義</p>';
$config->define('foo');
assert($config->foo === null);

try {
    $config->bar = true;
} catch (\InvalidArgumentException $e) {
    echo '<p>定義されていない項目へのアクセスは InvalidArgumentException</p>';
}


echo '<p>define()メソッドでは項目の初期値も定義できる</p>';
$config->define('bar', true);
assert($config->bar);


echo '<p>定義された項目は値を変更できる</p>';
$config->bar = false;
assert($config->bar === false);


try {
    $config->define('bar', false);
} catch (\InvalidArgumentException $e) {
    echo '<p>同名の項目は再定義できない</p>';
}


echo '<p>コンストラクタで定義できる</p>';
$config = new Configuration(array(
    'foo' => null,
    'bar' => true,
));
assert($config->foo === null);


echo '<p>プロパティアクセス、配列アクセスのどちらも実装済み</p>';
$config = new Configuration(array(
    'foo' => false,
    'bar' => true,
));
$config['foo'] = true;
$config->bar = false;
assert($config['foo'] === $config->foo);
assert($config['bar'] === $config->bar);


echo '<p>クロージャを値にして設定値を動的に※オプション</p>';
$config = new Configuration(array(
    'foo' => 0,
    'bar' => function($config) {
        return $config['foo'] * 2;
    },
), Configuration::EXECUTE_CALLABLE);
assert($config['bar'] === 0);
$config['foo'] = 1;
assert($config['bar'] === 2);
$config['foo'] = 5;
assert($config['bar'] === 10);


echo '<p>Countable,Traversableも実装済み</p>';
$config = new Configuration(array(
    'foo' => false,
    'bar' => true,
));
assert(count($config) === 2);

foreach ($config as $name => $value) {
    switch ($name) {
    case 'foo':
        assert($value === false);
        break;
    case 'bar':
        assert($value === true);
        break;
    }
}

echo '<p>再帰的に配列アクセス・プロパティアクセス</p>';
$config = new Configuration(array(
    'array' => array('a' => 'A', 'b' => 'B', 'c' => 'C'),
    'object' => new \ArrayObject(array(
        'a' => new \ArrayObject(array(
            'a' => array('a' => 'A', 'b' => 'B', 'c' => array('a' => 'A', 'b' => 'B', 'c'=> 'C')),
        )),
    )),
));

assert('A' === $config['array']['a']);
assert('B' === $config['array']['b']);
assert('C' === $config['array']['c']);
assert('A' === $config['object']['a']['a']['a']);
assert('B' === $config['object']['a']['a']['b']);
assert('A' === $config['object']['a']['a']['c']['a']);
assert('B' === $config['object']['a']['a']['c']['b']);
assert('C' === $config['object']['a']['a']['c']['c']);
assert('A' === $config->array->a);
assert('B' === $config->array->b);
assert('C' === $config->array->c);
assert('A' === $config->object->a->a->a);
assert('B' === $config->object->a->a->b);
assert('A' === $config->object->a->a->c->a);
assert('B' === $config->object->a->a->c->b);
assert('C' === $config->object->a->a->c->c);

echo '<p>実体はオブジェクトなので、純粋な配列への変換はtoArray()メソッドで</p>';
$array = $config->toArray();
assert('A' === $config['array']['a']);
assert('B' === $config['array']['b']);
assert('C' === $config['array']['c']);
assert('A' === $config['object']['a']['a']['a']);
assert('B' === $config['object']['a']['a']['b']);
assert('A' === $config['object']['a']['a']['c']['a']);
assert('B' === $config['object']['a']['a']['c']['b']);
assert('C' === $config['object']['a']['a']['c']['c']);
assert(is_array($array));
assert(is_array($array['array']));
assert(is_array($array['object']));
assert(is_array($array['object']['a']));

echo '<p>JSON文字列から生成</p>';
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
assert($config['foo'] === true);
assert($config['bar'] === false);
assert('A' === $config['arr'][0]);
assert('B' === $config['arr'][1]);
assert('C' === $config['arr'][2]);
assert('A' === $config['dict']['a']);
assert('B' === $config['dict']['b']);
assert('C' === $config['dict']['c']);

highlight_file(__FILE__);
