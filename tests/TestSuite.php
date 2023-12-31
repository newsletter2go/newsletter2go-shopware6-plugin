<?php declare(strict_types=1);

namespace Newsletter2go;

use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class TestSuite extends TestCase
{
    use IntegrationTestBehaviour;

    public function testClassesAreInstantiable()
    {
        $namespace = str_replace('Tests', '', __NAMESPACE__);

        foreach ($this->getPluginClasses() as $class) {
            $classRelativePath = str_replace(['.php', '/'], ['', '\\'], $class->getRelativePathname());

            $this->getMockBuilder($namespace . '\\' . $classRelativePath)
                ->disableOriginalConstructor()
                ->getMock();
        }

        // Nothing broke so far, classes seem to be instantiable
        $this->assertTrue(true);
    }

    private function getPluginClasses(): Finder
    {
        $finder = new Finder();
        $finder->in(realpath(__DIR__ . '/newsletter2go/'));
        $finder->exclude('Test');
        return $finder->files()->name('*.php');
    }
}
