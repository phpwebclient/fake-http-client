<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler;

use InvalidArgumentException;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;
use Webclient\Fake\Handler\SpecHandler\Condition\AndCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\ContainsComparer;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Comparer\MatchComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\NotCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\OrCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestBodyCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestHeaderCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestMethodCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestProtocolVersionCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestQueryCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestUriCondition;

final class Rule
{
    private bool $or;

    /**
     * @var ConditionInterface[]
     */
    private array $conditions = [];

    public function __construct(bool $or = false)
    {
        $this->or = $or;
    }

    /**
     * @param callable(Rule): void $fn
     * @return $this
     */
    public function allOf(callable $fn): self
    {
        $rule = new self();
        $fn($rule);
        $this->conditions[] = $rule->getCondition();
        return $this;
    }

    /**
     * @param callable(Rule): void $fn
     * @return $this
     */
    public function oneOf(callable $fn): self
    {
        $rule = new self(true);
        $fn($rule);
        $this->conditions[] = $rule->getCondition();
        return $this;
    }

    public function equal(string $field, ?string $pattern): self
    {
        $this->conditions[] = $this->createEqualCondition($field, $pattern);
        return $this;
    }

    public function match(string $field, ?string $pattern): self
    {
        $this->conditions[] = $this->createMatchCondition($field, $pattern);
        return $this;
    }

    public function contains(string $field, ?string $pattern): self
    {
        $this->conditions[] = $this->createContainsCondition($field, $pattern);
        return $this;
    }

    public function notEqual(string $field, ?string $pattern): self
    {
        $this->conditions[] = new NotCondition($this->createEqualCondition($field, $pattern));
        return $this;
    }

    public function notMatch(string $field, ?string $pattern): self
    {
        $this->conditions[] = new NotCondition($this->createMatchCondition($field, $pattern));
        return $this;
    }

    public function notContains(string $field, ?string $pattern): self
    {
        $this->conditions[] = new NotCondition($this->createContainsCondition($field, $pattern));
        return $this;
    }

    public function getCondition(): ConditionInterface
    {
        if (empty($this->conditions)) {
            return new AndCondition();
        }
        if (count($this->conditions) === 1) {
            return $this->conditions[0];
        }
        return $this->or ? new OrCondition(...$this->conditions) : new AndCondition(...$this->conditions);
    }

    private function createEqualCondition(string $field, ?string $pattern): ConditionInterface
    {
        return $this->createRequestPartCondition(new EqualComparer(), $field, $pattern);
    }

    private function createMatchCondition(string $field, ?string $pattern): ConditionInterface
    {
        return $this->createRequestPartCondition(new MatchComparer(), $field, $pattern);
    }

    private function createContainsCondition(string $field, ?string $pattern): ConditionInterface
    {
        return $this->createRequestPartCondition(new ContainsComparer(), $field, $pattern);
    }

    private function createRequestPartCondition(
        ComparerInterface $comparer,
        string $field,
        ?string $pattern
    ): ConditionInterface {
        switch ($field) {
            case 'method':
                return new RequestMethodCondition($comparer, $pattern);
            case 'protocolVersion':
                return new RequestProtocolVersionCondition($comparer, $pattern);
            case 'uri':
                return new RequestUriCondition($comparer, $pattern, null);
            case 'uri.scheme': // no break
            case 'uri.userInfo': // no break
            case 'uri.authority': // no break
            case 'uri.host': // no break
            case 'uri.port': // no break
            case 'uri.path': // no break
            case 'uri.query': // no break
            case 'uri.fragment':
                return $this->createRequestUriCondition($comparer, $field, $pattern);
            case 'body':
                return new RequestBodyCondition($comparer, $pattern);
        }

        if (strpos($field, 'query.') === 0) {
            $param = substr($field, 6);
            return new RequestQueryCondition($comparer, $pattern, $param);
        }
        if (strpos($field, 'header.') === 0) {
            $header = substr($field, 7);
            return new RequestHeaderCondition($comparer, $pattern, $header);
        }

        $allowed = [
            'method',
            'protocolVersion',
            'uri',
            'uri.scheme',
            'uri.host',
            'uri.port',
            'uri.path',
            'uri.authority',
            'uri.userInfo',
            'uri.query',
            'uri.fragment',
            'body',
            'query.*',
        ];
        $last = 'header.*';

        throw new InvalidArgumentException(
            sprintf('parameter field must be "%s" or "%s". passed: %s', implode('", "', $allowed), $last, $field)
        );
    }

    private function createRequestUriCondition(
        ComparerInterface $comparer,
        string $field,
        ?string $pattern
    ): ConditionInterface {
        $part = substr($field, 4);
        return new RequestUriCondition($comparer, $pattern, $part);
    }
}
