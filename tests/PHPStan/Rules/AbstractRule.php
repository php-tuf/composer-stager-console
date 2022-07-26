<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/** Provides a base class for PHPStan rules. */
abstract class AbstractRule implements Rule
{
    protected const PROJECT_ROOT = __DIR__ . '/../../../';

    protected ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    protected function getClassReflection(Node $node): ?ClassReflection
    {
        if (!isset($node->namespacedName)) {
            return null;
        }

        $namespace = $node->namespacedName;
        assert($namespace instanceof Name);

        return $this->reflectionProvider->getClass($namespace->toString());
    }

    protected function getMethodReflection(Scope $scope): MethodReflection
    {
        $methodReflection = $scope->getFunction();

        if (!$methodReflection instanceof MethodReflection) {
            throw new ShouldNotHappenException();
        }

        return $methodReflection;
    }

    protected function isProjectClass(ClassReflection $class): bool
    {
        return $this->isInNamespace($class->getName(), 'PhpTuf\ComposerStagerConsole\\');
    }

    protected function isFactoryClass(ClassReflection $class): bool
    {
        $factory = 'Factory';

        return substr($class->getName(), -strlen($factory)) === $factory;
    }

    protected function isThrowable(ClassReflection $class): bool
    {
        return array_key_exists('Throwable', $class->getInterfaces());
    }

    protected function isInNamespace(string $name, string $namespace): bool
    {
        return strpos($name, $namespace) === 0;
    }

    protected function getNamespace(string $name): string
    {
        $nameParts = explode('\\', $name);
        array_pop($nameParts);

        return implode('\\', $nameParts);
    }
}
