# PHP Code AST

PHP code generation based on AST.

## Installation

```bash
$ composer require open-code-modeling/php-code-ast --dev
```

## Usage

Let's start with a straightforward example of generating a class with the `ClassFactory`:

```php
<?php

$parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::ONLY_PHP7);
$printer = new PhpParser\PrettyPrinter\Standard(['shortArraySyntax' => true]);

$ast = $parser->parse('');

$classFactory = OpenCodeModeling\CodeAst\Factory\ClassFactory::fromScratch('TestClass', 'My\\Awesome\\Service');
$classFactory
    ->setFinal(true)
    ->setExtends('BaseClass')
    ->setNamespaceUse('Foo\\Bar')
    ->setImplements('\\Iterator', 'Bar');

$nodeTraverser = new PhpParser\NodeTraverser();

$classFactory->injectVisitors($nodeTraverser);

print_r($printer->prettyPrintFile($nodeTraverser->traverse($ast)));
```

Will print the following output:

```php
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
}
```

All this is done via PHP AST and it checks if the AST token is already defined. So it will not override your code.

You can also use the code and PHP AST visitor classes to generate code via the low level API.
To add a method `toInt()` to the class above you can add this via the `ClassMethod` visitor.

```php
<?php

$method = new OpenCodeModeling\CodeAst\Code\MethodGenerator(
    'toInt',
    [],
    OpenCodeModeling\CodeAst\Code\MethodGenerator::FLAG_PUBLIC,
    new OpenCodeModeling\CodeAst\Code\BodyGenerator($this->parser, 'return $this->myValue;')
);
$method->setReturnType('int');

$nodeTraverser->addVisitor(new OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod($method));

print_r($printer->prettyPrintFile($nodeTraverser->traverse($ast)));
```

This will print the following output.

```php
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
    public function toInt() : int
    {
        return $this->myValue;
    }
}
```

Now, change the body of the `toInt()` method to something else. You will see that your changes will *NOT* be overwritten.

### Reverse usage

It is also possible to create a factory class from parsed PHP AST. You can create an instance of `OpenCodeModeling\CodeAst\Factory\ClassFactory` by 
calling `OpenCodeModeling\CodeAst\Factory\ClassFactory::fromNodes()`.

```php
<?php
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
    private const PRIV = 'private';
}
EOF;


$ast = $parser->parse($expected);

$classFactory = OpenCodeModeling\CodeAst\Factory\ClassFactory::fromNodes(...$ast);

$classFactory->getName(); // TestClass
$classFactory->getExtends(); // BaseClass
$classFactory->isFinal(); // true
$classFactory->isStrict(); // true
$classFactory->isAbstract(); // false

```
