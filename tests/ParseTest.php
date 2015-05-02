<?php

use crodas\ClassInfo\ClassInfo;
use crodas\ClassInfo\Definition\TClass;
use crodas\ClassInfo\Definition\TFunction;
use crodas\ClassInfo\Definition\TProperty;

class ParseTest extends \phpunit_framework_testcase
{
    public function testParse()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');
        
        $classes   = $parser->getClasses();
        $functions = $parser->getFunctions();

        $this->assertTrue(!empty($classes));
        $this->assertEquals(count($functions), 2);

        foreach ($classes as $class) {
            do {
                $this->assertTrue($class instanceof TClass);
                foreach ($class->getInterfaces() as $interface) {
                    $this->assertTrue($interface instanceof TClass);
                    $this->assertEquals($interface->getType(), 'interface');
                }
                foreach ($class->getTraits() as $interface) {
                    $this->assertTrue($interface instanceof TClass);
                    $this->assertEquals($interface->getType(), 'trait');
                }
                foreach ($class->getMethods() as $method) {
                    $this->assertTrue($method instanceof TFunction);
                }
                foreach ($class->getProperties() as $method) {
                    $this->assertTrue($method instanceof TProperty);
                }
            } while ($class = $class->GetParent());
        }

        foreach (array_merge($functions, $classes) as $class) {
            $this->assertEquals(substr($class->getName(), 0, 6), "xxx\\yy");
        }

        $functions = $parser->getClass('xxx\yy\foobar')->getMethods();
        $this->assertEquals(count($functions), 2);
        $this->assertEquals(array('$x', '$y'), $functions['xx']->getParameters());

        $functions = $parser->getClass('xxx\yy\foo')->getMethods();
        $this->assertEquals(count($functions), 1);

        $functions = $parser->getClass('xxx\yy\foo')->getProperties();
        $this->assertEquals(count($functions), 3);
    }

    public function testAnnotation()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');

        $class = $parser->getClass('xxx\yy\foo');
        $this->assertEquals($class->getPHPDoc(), '/** @class */');
        $this->assertEquals($class->getProperty('$xxx')->getPHPDoc(), '/** @foobar */');
        $this->assertEquals($class->getMethod('somename')->getPHPDoc(), '/** @something */');
    }

    public function testClass()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');

        $class = $parser->getClass('xxx\yy\zzz');
        $this->assertFalse($class->isUserDefined());

        $class = $parser->getClass('xxx\yy\foo');
        $this->assertTrue($class->isUserDefined());
        $this->assertFalse($class->isFinal());
        $this->assertFalse($class->isAbstract());
        $this->assertTrue($class->getStartLine() > 1);
        $this->assertTrue($class->getEndLine() > 1);
    }

    public function testMultiple()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');
        $parser->parse(__DIR__ . '/features/2.php');

        $class = $parser->getClass('xxx\yy\zzz');
        $this->assertTrue($class->isUserDefined());

        $class = $parser->getClass('xxx\yy\foo');
        $this->assertTrue($class->isUserDefined());
        $this->assertFalse($class->isFinal());
        $this->assertFalse($class->isAbstract());
    }

    public function testMethods()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');

        $class = $parser->getClass('xxx\yy\foo');

        $method = $class->getMethod('somename');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isAbstract());
        $this->assertTrue($method->getStartLine() < $method->getEndLine());
    }

    public function testProperties()
    {
        $parser = new ClassInfo();
        $parser->parse(__DIR__ . '/features/1.php');

        $class = $parser->getClass('xxx\yy\foo');
        $props = $class->getProperties();

        $prop = $props['$bar'];
        $this->assertFalse($prop->isPublic());
        $this->assertTrue($prop->isProtected());
        $this->assertFalse($prop->isStatic());
        $this->assertFalse($prop->isPrivate());

        $prop = $props['$xxx'];
        $this->assertTrue($prop->isPublic());
        $this->assertFalse($prop->isProtected());
        $this->assertFalse($prop->isStatic());
        $this->assertFalse($prop->isPrivate());
        $this->assertTrue($prop->getStartLine() > 1);
        $this->assertEquals('/** @foobar */', $prop->getPHPDoc());

        $prop = $props['$foo'];
        $this->assertTrue($prop->isPublic());
        $this->assertFalse($prop->isProtected());
        $this->assertTrue($prop->isStatic());
        $this->assertFalse($prop->isPrivate());
        $this->assertEquals('/**  */', $prop->getPHPDoc());
    }
        
}
