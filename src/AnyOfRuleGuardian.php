<?php

namespace Aegisora\RuleGuardians\AnyOfRule;

use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\Guardian\Guardian;
use Aegisora\RuleContract\Models\RuleContext;
use Aegisora\RuleContract\Models\RuleContextCollection;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\ExceptionMustBeLastException;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\MissingRuleContextException;
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\UnexpectedArgumentException;
use Aegisora\Rules\AnyOfRule;
use Throwable;

class AnyOfRuleGuardian
{
    private Guardian $guardian;

    public function __construct(
        Guardian $guardian
    ) {
        $this->guardian = $guardian;
    }

    /**
     * @param RuleContext|Throwable ...$arguments
     * @throws GuardianExecutingRuleException
     * @throws GuardianValidationException
     * @throws ExceptionMustBeLastException
     * @throws UnexpectedArgumentException
     * @throws MissingRuleContextException
     * @throws Throwable
     */
    public function check(...$arguments): void
    {
        $exception = null;
        $ruleContexts = [];
        $lastIndex = count($arguments) - 1;

        foreach ($arguments as $index => $argument) {
            if ($argument instanceof Throwable) {
                if ($index !== $lastIndex) {
                    throw new ExceptionMustBeLastException();
                }

                $exception = $argument;

                continue;
            }

            if (!$argument instanceof RuleContext) {
                throw new UnexpectedArgumentException();
            }

            $ruleContexts[] = $argument;
        }

        if ($ruleContexts === []) {
            throw new MissingRuleContextException();
        }

        $this->guardian->check(
            RuleContextCollection::create(...$ruleContexts),
            AnyOfRule::create(),
            $exception
        );
    }
}
