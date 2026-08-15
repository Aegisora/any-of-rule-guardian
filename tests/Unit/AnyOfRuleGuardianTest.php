<?php

namespace Aegisora\RuleGuardians\AnyOfRule\Tests\Unit;

use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\Guardian\Guardian;
use Aegisora\RuleContract\Models\RuleContext;
use Aegisora\RuleGuardians\AnyOfRule\AnyOfRuleGuardian;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\ExceptionMustBeLastException;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\MissingRuleContextException;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\UnexpectedArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class AnyOfRuleGuardianTest extends TestCase
{
    private AnyOfRuleGuardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardian = new AnyOfRuleGuardian(
            new Guardian()
        );
    }

    /**
     * @dataProvider getSuccessfullyCheckProvidedData
     * @param array<int, RuleContext|CustomRuleException> $arguments
     */
    public function testSuccessfullyCheck(array $arguments): void
    {
        $this->expectNotToPerformAssertions();

        $this->guardian->check(...$arguments);
    }

    public static function getSuccessfullyCheckProvidedData(): array
    {
        return [
            'single valid context' => [
                'arguments' => [
                    self::validRuleContext(),
                ],
            ],
            'multiple contexts - first is valid' => [
                'arguments' => [
                    self::validRuleContext(),
                    self::invalidRuleContext(),
                ],
            ],
            'multiple contexts - last is valid' => [
                'arguments' => [
                    self::invalidRuleContext(),
                    self::validRuleContext(),
                ],
            ],
            'multiple contexts - all are valid' => [
                'arguments' => [
                    self::validRuleContext(),
                    self::validRuleContext(),
                ],
            ],
            'valid context with custom exception - exception is ignored on success' => [
                'arguments' => [
                    self::validRuleContext(),
                    new CustomRuleException(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFailedCheckProvidedData
     * @param array<int, RuleContext|CustomRuleException> $arguments
     */
    public function testFailedCheck(
        array $arguments,
        string $expectedExceptionClassName
    ): void {
        $this->expectException($expectedExceptionClassName);

        $this->guardian->check(...$arguments);
    }

    public static function getFailedCheckProvidedData(): array
    {
        return [
            'single invalid context - custom exception not set' => [
                'arguments' => [
                    self::invalidRuleContext(),
                ],
                'expectedExceptionClassName' => GuardianValidationException::class,
            ],
            'multiple invalid contexts - custom exception not set' => [
                'arguments' => [
                    self::invalidRuleContext(),
                    self::invalidRuleContext(),
                ],
                'expectedExceptionClassName' => GuardianValidationException::class,
            ],
            'multiple invalid contexts - custom exception set' => [
                'arguments' => [
                    self::invalidRuleContext(),
                    self::invalidRuleContext(),
                    new CustomRuleException()
                ],
                'expectedExceptionClassName' => CustomRuleException::class,
            ],
        ];
    }

    /**
     * @dataProvider getMissingRuleContextProvidedData
     * @param array<int, CustomRuleException> $arguments
     */
    public function testCheckThrowsMissingRuleContextException(array $arguments): void
    {
        $this->expectException(MissingRuleContextException::class);

        $this->guardian->check(...$arguments);
    }

    public static function getMissingRuleContextProvidedData(): array
    {
        return [
            'no arguments' => [
                'arguments' => [],
            ],
            'only custom exception' => [
                'arguments' => [
                    new CustomRuleException(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getExceptionMustBeLastProvidedData
     * @param array<int, RuleContext|CustomRuleException> $arguments
     */
    public function testCheckThrowsExceptionMustBeLastException(array $arguments): void
    {
        $this->expectException(ExceptionMustBeLastException::class);

        $this->guardian->check(...$arguments);
    }

    public static function getExceptionMustBeLastProvidedData(): array
    {
        return [
            'exception before context' => [
                'arguments' => [
                    new CustomRuleException(),
                    self::validRuleContext(),
                ],
            ],
            'two exceptions' => [
                'arguments' => [
                    new CustomRuleException(),
                    new CustomRuleException(),
                ],
            ],
            'context followed by two exceptions' => [
                'arguments' => [
                    self::validRuleContext(),
                    new CustomRuleException(),
                    new CustomRuleException(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getUnexpectedArgumentProvidedData
     * @param mixed $argument
     */
    public function testCheckThrowsUnexpectedArgumentException($argument): void
    {
        $this->expectException(UnexpectedArgumentException::class);

        $this->guardian->check($argument);
    }

    public static function getUnexpectedArgumentProvidedData(): array
    {
        return [
            'argument - zero integer' => [
                'argument' => 0,
            ],
            'argument - positive integer' => [
                'argument' => 1,
            ],
            'argument - float' => [
                'argument' => 1.5,
            ],
            'argument - true' => [
                'argument' => true,
            ],
            'argument - false' => [
                'argument' => false,
            ],
            'argument - null' => [
                'argument' => null,
            ],
            'argument - not empty string' => [
                'argument' => 'foo',
            ],
            'argument - empty string' => [
                'argument' => '',
            ],
            'argument - not empty array' => [
                'argument' => [123],
            ],
            'argument - empty array' => [
                'argument' => [],
            ],
            'argument - object' => [
                'argument' => new stdClass(),
            ],
            'argument - callable' => [
                'argument' => static function () {
                },
            ],
        ];
    }

    private static function validRuleContext(): RuleContext
    {
        return RuleContext::createFromValue(new ValidTestRule(), null);
    }

    private static function invalidRuleContext(): RuleContext
    {
        return RuleContext::createFromValue(new InvalidTestRule(), null);
    }
}
