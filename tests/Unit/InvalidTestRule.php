<?php

namespace Aegisora\RuleGuardians\AnyOfRule\Tests\Unit;

use Aegisora\RuleContract\Models\Context;
use Aegisora\RuleContract\Models\Result;
use Aegisora\RuleContract\Rule;

class InvalidTestRule extends Rule
{
    protected function executeValidate(Context $context): Result
    {
        return $this->getDefaultInvalidResult();
    }
}
