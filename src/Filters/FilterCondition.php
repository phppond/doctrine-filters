<?php

namespace PhpPond\Filters;


use InvalidArgumentException;

/**
 * Class FilterCondition
 *
 * @package PhpPondFilters\Filter
 *
 * @author  Nick G. Lavrik <nick.lavrik@gmail.com>
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FilterCondition
{

    const LIKE    = 'like';
    const EQUALS  = 'eq';
    const GT      = 'gt';
    const GTE     = 'gte';
    const LT      = 'lt';
    const LTE     = 'lte';
    const GROUP   = 'group';
    const IN      = 'in';

    private $property;
    private $value;
    private $type;
    private $isNegated = false;
    private $orConditions = array();

    /**
     * @param string      $type
     * @param string|null $property
     * @param mixed|null  $value
     * @param bool        $isNegated
     *
     * @throws InvalidArgumentException
     */
    public function __construct($type, $property = null, $value = null, $isNegated = false)
    {

        if (!in_array($type, static::allConditionTypes())) {
            throw new InvalidArgumentException('Illegal FilterCondition type', $type);
        }

        $this->type = $type;
        $this->property = $property;
        $this->value = $value;
        $this->isNegated = (boolean) $isNegated;
    }

    /**
     * @return FilterCondition
     */
    public static function group()
    {
        $group = new FilterCondition(self::GROUP);
        foreach (func_get_args() as $oArg) {
            $group->addCondition($oArg);
        }

        return $group;
    }

    /**
     * @param FilterCondition $condition
     *
     * @return FilterCondition The original condition for method-chaining
     */
    public function addCondition(FilterCondition $condition)
    {
        $this->orConditions[] = $condition;

        return $this;
    }

    /**
     * @return FilterCondition[]
     */
    public function getConditions()
    {
        return $this->orConditions;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getPropertyName()
    {
        return $this->property;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isNegated()
    {
        return $this->isNegated;
    }

    /**
     * @param bool $isNegated
     */
    public function setNegated($isNegated)
    {
        $this->isNegated = $isNegated;
    }

    /**
     * @return bool
     */
    public function isEquals()
    {
        return $this->type == self::EQUALS;
    }

    /**
     * @return bool
     */
    public function isLike()
    {
        return $this->type == self::LIKE;
    }

    /**
     * @return bool
     */
    public function isGTE()
    {
        return $this->type == self::GTE;
    }

    /**
     * @return bool
     */
    public function isGT()
    {
        return $this->type == self::GT;
    }

    /**
     * @return bool
     */
    public function isLTE()
    {
        return $this->type == self::LTE;
    }

    /**
     * @return bool
     */
    public function isLT()
    {
        return $this->type == self::LT;
    }

    /**
     * @return bool
     */
    public function isIn()
    {
        return $this->type == self::IN;
    }

    /**
     * @return bool
     */
    public function isGroup()
    {
        return $this->type == self::GROUP;
    }

    /**
     * @return array
     */
    protected function allConditionTypes()
    {
        return [
            self::LIKE,
            self::EQUALS,
            self::GT,
            self::GTE,
            self::LT,
            self::LTE,
            self::GROUP,
            self::IN,
        ];
    }

    /**
     * @return array
     */
    protected function allConditions()
    {
        return [
            self::LIKE   => 'Like',
            self::EQUALS => 'Equals',
            self::GT     => 'Greater Than',
            self::GTE    => 'Greater Than Or Equal',
            self::LT     => 'Less Than',
            self::LTE    => 'Less Than or Equal',
            self::IN     => 'IN ()',
        ];
    }
}
