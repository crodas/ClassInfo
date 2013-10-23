ClassInfo
=========

Get classes and functions defined in a given file. It implements a tiny PHP parser which gives you detailed informations about classes and functions defined in a file.

How to use it
-------------

```php
<?php
require __DIR__ . "/vendor/autoload.php";

$parser = new crodas\ClassInfo\ClassInfo;
$parser->parse('demo.php');

foreach ($parser->getClasses() as $class) {
    foreach ($class->getMethods() as $method) {
    }
    foreach ($class->getProperties() as $prop) {
    }
    foreach ($class->getInterfaces() as $class) {
    }
}

```
