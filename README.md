# Aegisora Any Of Rule Guardian

[![Latest Version](https://img.shields.io/packagist/v/aegisora/any-of-rule-guardian?style=flat-square)](https://packagist.org/packages/aegisora/any-of-rule-guardian)
[![Total Downloads](https://img.shields.io/packagist/dt/aegisora/any-of-rule-guardian?style=flat-square)](https://packagist.org/packages/aegisora/any-of-rule-guardian)
![Code Coverage Badge](./badge.svg)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)

Any Of Rule Guardian provides a simple shortcut for "any of" validation using `aegisora/guardian` and `aegisora/any-of-rule`.

It is designed for cases where you want to quickly check whether **at least one** of several rule contexts is valid, without manually building an `AnyOfRule` and a validation pipeline by hand.

This package is built on top of:

* [aegisora/guardian](https://github.com/Aegisora/guardian)
* [aegisora/any-of-rule](https://github.com/Aegisora/any-of-rule)

---

## ✨ Features

* 🔹 Simple shortcut API for `AnyOfRule`
* 🔹 Validates that **at least one** of the provided rule contexts passes (logical OR)
* 🔹 Accepts an arbitrary number of `RuleContext` objects
* 🔹 Uses `aegisora/guardian` internally
* 🔹 Uses `aegisora/any-of-rule` internally
* 🔹 Supports a custom validation exception
* 🔹 Ships dedicated, typed exceptions for invalid usage
* 🔹 Keeps rule execution errors separated from validation errors
* 🔹 Fully compatible with the Aegisora ecosystem
* 🔹 Ready to use out of the box

---

## 📦 Installation

```bash
composer require aegisora/any-of-rule-guardian
```

---

## 🚀 Core Concept

This package wraps the common "any of" validation flow:

```php
$guardian->check(
    RuleContextCollection::create($ruleContextA, $ruleContextB),
    AnyOfRule::create(),
    new NoneMatchedException()
);
```

into a dedicated shortcut class:

```php
$anyOfRuleGuardian->check($ruleContextA, $ruleContextB, new NoneMatchedException());
```

Instead of manually assembling a `RuleContextCollection`, creating an `AnyOfRule`, and passing them to `Guardian`, you can use `AnyOfRuleGuardian` directly.

---

## 🏗️ Basic Usage

```php
use Aegisora\Guardian\Guardian;
use Aegisora\Guardian\Exceptions\GuardianValidationException;
use Aegisora\RuleContract\Models\RuleContext;
use Aegisora\RuleGuardians\AnyOfRule\AnyOfRuleGuardian;

$guardian = new Guardian();

$anyOfRuleGuardian = new AnyOfRuleGuardian($guardian);

$ruleContextA = RuleContext::createFromValue($ruleA, $valueA);
$ruleContextB = RuleContext::createFromValue($ruleB, $valueB);

try {
    $anyOfRuleGuardian->check($ruleContextA, $ruleContextB);
    // at least one rule context is valid
} catch (GuardianValidationException $exception) {
    // none of the rule contexts are valid
}
```

Validation **passes as soon as one** of the provided rule contexts is valid, and **fails only when all** of them are invalid.

---

## ✅ How "any of" works

You pass one or more `RuleContext` objects. Each context pairs a rule with the value it should validate:

```php
// short form (aegisora/rule-contract >= 1.2.0)
$ruleContext = RuleContext::createFromValue($rule, $value);

// explicit form
$ruleContext = RuleContext::create($rule, Context::create($value));
```

Then `check()` succeeds if any of them is valid:

```php
$anyOfRuleGuardian->check($ruleContextA);                 // one context
$anyOfRuleGuardian->check($ruleContextA, $ruleContextB);  // many contexts
```

At least one `RuleContext` is required. Calling `check()` with no rule context throws an [invalid-usage exception](#-exceptions).

---

## 🧩 Usage with Custom Exception

You may provide your own exception for validation failure. It must be the **last** argument.

```php
use Aegisora\Guardian\Guardian;
use Aegisora\RuleContract\Models\RuleContext;
use Aegisora\RuleGuardians\AnyOfRule\AnyOfRuleGuardian;
use App\Exceptions\NoneMatchedException;

$guardian = new Guardian();

$anyOfRuleGuardian = new AnyOfRuleGuardian($guardian);

$anyOfRuleGuardian->check(
    RuleContext::createFromValue($ruleA, $valueA),
    RuleContext::createFromValue($ruleB, $valueB),
    new NoneMatchedException()
);
```

If none of the rule contexts are valid, the provided exception will be thrown instead of `GuardianValidationException`.

This is useful when validation errors should have domain-specific meaning.

---

## 🧪 Example in Application Service

```php
use Aegisora\RuleContract\Models\RuleContext;
use Aegisora\RuleGuardians\AnyOfRule\AnyOfRuleGuardian;
use App\Exceptions\PaymentMethodRequiredException;

final class CheckoutService
{
    private AnyOfRuleGuardian $anyOfRuleGuardian;

    public function __construct(
        AnyOfRuleGuardian $anyOfRuleGuardian
    ) {
        $this->anyOfRuleGuardian = $anyOfRuleGuardian;
    }

    public function ensurePayable(Order $order): void
    {
        $this->anyOfRuleGuardian->check(
            RuleContext::createFromValue($this->hasStoredCardRule, $order),
            RuleContext::createFromValue($this->hasEnoughBalanceRule, $order),
            new PaymentMethodRequiredException()
        );

        // business logic for a payable order
    }
}
```

---

## 🚨 Exceptions

The package raises two kinds of exceptions:

* **Validation / execution exceptions** delegated to `Guardian` (the outcome of running the rule).
* **Usage exceptions** owned by this package (the arguments passed to `check()` are invalid).

### `GuardianValidationException`

Thrown when validation fails (all rule contexts are invalid) and no custom exception is provided.

The rule code for failed "any of" validation is `any_of_rule`.

```php
use Aegisora\Guardian\Exceptions\GuardianValidationException;

try {
    $anyOfRuleGuardian->check($ruleContextA, $ruleContextB);
} catch (GuardianValidationException $exception) {
    echo $exception->getRuleCode(); // "any_of_rule"
}
```

### Custom exception

When a custom exception is passed as the last argument, it is thrown instead of `GuardianValidationException` on validation failure.

```php
use App\Exceptions\NoneMatchedException;

try {
    $anyOfRuleGuardian->check($ruleContextA, $ruleContextB, new NoneMatchedException());
} catch (NoneMatchedException $exception) {
    // domain-specific handling
}
```

### `GuardianExecutingRuleException`

Thrown when one of the underlying rules fails to execute (raises a `RuleException` during validation), as opposed to simply reporting an invalid result.

```php
use Aegisora\Guardian\Exceptions\GuardianExecutingRuleException;

try {
    $anyOfRuleGuardian->check($ruleContextA);
} catch (GuardianExecutingRuleException $exception) {
    // a rule could not be executed
}
```

### Usage exceptions

These exceptions signal that `check()` was called incorrectly. They all extend the abstract base `AnyOfRuleGuardianException`, so you can catch the whole group at once:

```php
use Aegisora\RuleGuardians\AnyOfRule\Exceptions\AnyOfRuleGuardianException;

try {
    $anyOfRuleGuardian->check(...$arguments);
} catch (AnyOfRuleGuardianException $exception) {
    // check() was called with invalid arguments
}
```

| Exception | Thrown when |
|---|---|
| `MissingRuleContextException` | No `RuleContext` was provided (empty call, or only a `Throwable`). |
| `ExceptionMustBeLastException` | A `Throwable` was passed in a non-last position, or more than one `Throwable` was provided. |
| `UnexpectedArgumentException` | An argument is neither a `RuleContext` nor a `Throwable` (e.g. `int`, `string`, `null`, `array`, arbitrary object). |

All three extend `AnyOfRuleGuardianException`.

---

## 🧩 API

### `AnyOfRuleGuardian::check()`

```php
/**
 * @param RuleContext|\Throwable ...$arguments
 * @throws GuardianExecutingRuleException
 * @throws GuardianValidationException
 * @throws ExceptionMustBeLastException
 * @throws UnexpectedArgumentException
 * @throws MissingRuleContextException
 * @throws \Throwable
 */
public function check(...$arguments): void
```

Validates that **at least one** of the provided rule contexts is valid.

Arguments:

* `...$arguments` — one or more `RuleContext` objects to validate, optionally followed by a **single** `\Throwable`, as the **last** argument, to be thrown on failure.

The method returns `void`. It communicates results through exceptions only — it returns nothing on success and throws on failure:

* `GuardianValidationException` — all rule contexts are invalid and no custom exception was provided
* the provided custom exception — all rule contexts are invalid and a custom exception was passed
* `GuardianExecutingRuleException` — an underlying rule failed to execute
* `MissingRuleContextException` / `ExceptionMustBeLastException` / `UnexpectedArgumentException` — the arguments passed to `check()` are invalid

Valid calls:

```php
$anyOfRuleGuardian->check($ruleContextA);
$anyOfRuleGuardian->check($ruleContextA, $ruleContextB);
$anyOfRuleGuardian->check($ruleContextA, $ruleContextB, new NoneMatchedException());
```

Spreading an array of rule contexts (note: a positional argument after unpacking is not allowed, so append the exception to the unpacked list):

```php
$anyOfRuleGuardian->check(...$ruleContexts);
$anyOfRuleGuardian->check(...[...$ruleContexts, new NoneMatchedException()]);
```

---

## 🏛️ Architecture

This package is a small shortcut layer over the Aegisora validation pipeline.

Flow:

1. `AnyOfRuleGuardian::check()` is called with rule contexts and an optional exception
2. The arguments are validated (typed usage exceptions are thrown on misuse)
3. A `RuleContextCollection` is assembled and an `AnyOfRule` is created
4. `Guardian` executes the rule
5. If at least one context is valid, execution continues normally
6. If all contexts are invalid, the custom exception or `GuardianValidationException` is thrown
7. If a rule fails to execute, `GuardianExecutingRuleException` is thrown

Internal flow:

```
RuleContexts → AnyOfRuleGuardian → Guardian → AnyOfRule → Result → Exception
```

---

## 🔗 Related Packages

* [aegisora/guardian](https://github.com/Aegisora/guardian) — validation execution orchestrator
* [aegisora/any-of-rule](https://github.com/Aegisora/any-of-rule) — "any of" rule composition
* [aegisora/rule-contract](https://github.com/Aegisora/rule-contract) — base rule contract and validation result architecture

---

## ⚖️ License

This package is open-source and licensed under the MIT License. See the LICENSE for details.

---

## 🌱 Contributing

Contributions are welcome and greatly appreciated!. See the CONTRIBUTING for details.

---

## 🌟 Support

If you find this project useful, please consider giving it a star on GitHub!

It helps the project grow and motivates further development.
