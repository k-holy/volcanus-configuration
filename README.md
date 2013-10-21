#Volcanus_Configuration

[![Latest Stable Version](https://poser.pugx.org/volcanus/configuration/v/stable.png)](https://packagist.org/packages/volcanus/configuration)
[![Build Status](https://travis-ci.org/k-holy/volcanus-configuration.png?branch=master)](https://travis-ci.org/k-holy/volcanus-configuration)

##使い方

```php
<?php
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


echo '<p>Traversable(IteratorAggregate), Countableも実装済み</p>';
$config = new Configuration(array(
    'foo' => false,
    'bar' => true,
));
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
assert(count($config) === 2);

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
```

ver 0.3.0 以降 offsetExists() と offsetUnset() だけではなく __isset() および __unset() も実装しました。

恥ずかしながら、ずっとこのマジックメソッドの存在を忘れてました…。

```php
<?php

echo '<p>offsetExists() の実装による未定義キーへの isset()</p>';
$config = new Configuration(array(
    'foo' => false,
    'bar' => null,
));
assert(true === isset($config['foo']));
assert(false === isset($config['bar']));
assert(false === isset($config['baz']));

echo '<p>__isset() の実装による未定義プロパティへの isset()</p>';
$config = new Configuration(array(
    'foo' => false,
    'bar' => null,
));
assert(true === isset($config->foo));
assert(false === isset($config->bar));
assert(false === isset($config->baz));

echo '<p>offsetUnset() の実装による配列アクセスでの unset()</p>';
$config = new Configuration(array(
    'foo' => false,
));
assert(true === isset($config['foo']));
unset($config['foo']);
assert(false === isset($config['foo']));

echo '<p>__unset() の実装によるプロパティアクセスでの unset()</p>';
$config = new Configuration(array(
    'foo' => false,
));
assert(true === isset($config->foo));
unset($config->foo);
assert(false === isset($config->foo));
```

##変更履歴

* 0.4.2 define() 実行時のプロパティ存在確認を廃止、define() および offsetSet() 時のcallableな値とメソッドの存在確認は EXECUTE_CALLABLE フラグが有効な場合のみ行うよう修正
* 0.4.1 Countableインタフェースおよび count() の実装を復旧
* 0.4.0 import() を廃止、Countableインタフェースおよび count() の実装を廃止、define() 実行時にプロパティの存在を確認するよう修正
* 0.3.0 __isset(), __unset() を実装
* 0.2.3 属性値にcallableな文字列がセットされている際の offsetGet() および __call() の不具合を修正
* 0.2.2 createFromJson() を実装
* 0.2.1 toArray() による配列への変換時に属性値がcallableの場合は $executeCallable プロパティに従った値を返すよう修正
* 0.2.0 toArray() を実装
* 0.1.0 公開開始
