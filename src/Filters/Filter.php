<?php

namespace PhpPond\Filters;


use InvalidArgumentException;

/**
 * Class Filter
 *
 * @package PhpPondFilters\Filters
 *
 * @author  Nick G. Lavrik <nick.lavrik@gmail.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class Filter implements FilterInterface
{

    /** @var int */
    private $start;

    /** @var int */
    private $limit;

    /** @var array */
    private $orderings = array();

    /** @var FilterCondition[] */
    private $conditions = array();

    /** @var array */
    private $select = [];

    /**
     * @return FilterInterface|static
     */
    public function clear()
    {
        $this->start = null;
        $this->limit = null;
        $this->select = [];
        $this->orderings = [];
        $this->conditions = [];

        return $this;
    }

    /**
     * Copy setting across from another filter
     *
     * @param Filter $filter
     */
    public function copy(Filter $filter)
    {
        $this->start = $filter->getStart();
        $this->limit = $filter->getLimit();
        $this->orderings = $filter->getOrderings();
        $this->conditions = $filter->getConditions();
        $this->select = $filter->getSelect();
    }

    /**
     * Return SELECT fields collection
     *
     * @return array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param string      $select
     * @param string|null $alias
     *
     * @return FilterInterface
     *
     * todo: implement SelectFieldInterface (or something like it)
     */
    public function addSelect($select, $alias = null)
    {
        if (empty($alias)) {
            $alias = $select;
        }

        $this->select[$alias] = $select;

        return $this;
    }

    /**
     * @return FilterInterface
     */
    public function first()
    {
        $this->setStart(0);
        $this->setLimit(1);

        return $this;
    }

    /**
     * Set the start index
     *
     * @param int $start
     *
     * @return FilterInterface
     */
    public function setStart($start = null)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the limit on the number of objects to return
     *
     * @param int $limit
     *
     * @return FilterInterface|static
     */
    public function setLimit($limit = null)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set a property to order the result set by. This method should be able to be called more than once
     * and append the ordering to the list retrieved by getOrderings()
     *
     * @param string  $property
     * @param boolean $isAscending
     *
     * @return FilterInterface|static
     */
    public function addOrdering($property, $isAscending = true)
    {
        $this->orderings[] = [$property, $isAscending];

        return $this;
    }

    /**
     * @return FilterInterface
     */
    public function clearOrderings()
    {
        $this->orderings = [];

        return $this;
    }

    /**
     * Returns an array of orderings array(propertyName, ascending)
     *
     * @return array<string,boolean>
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * Add a FilterCondition
     * This method can be called multiple times. Successive conditions are ANDed
     *
     * @param FilterCondition $condition
     *
     * @return FilterInterface|static
     */
    public function addCondition(FilterCondition $condition)
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @return FilterInterface|static
     */
    public function clearConditions()
    {
        $this->conditions = [];

        return $this;
    }

    /**
     * Returns an array of FilterConditions
     *
     * @return FilterCondition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Returns true if one or more conditions has been set for the named property
     *
     * @param string  $conditionType
     * @param string  $property
     * @param boolean $isNegated
     *
     * @return boolean
     */
    public function hasConditionFor($conditionType, $property, $isNegated = false)
    {
        foreach ($this->conditions as $condition) {
            if ($condition->isNegated() !== $isNegated) {
                continue;
            }
            if ($condition->getPropertyName() !== $property) {
                continue;
            }
            if ($condition->getType() !== $conditionType) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the first condition of the given type on the named property
     *
     * @param string $conditionType
     * @param string $property
     *
     * @return FilterCondition
     *
     * @throws InvalidArgumentException
     */
    public function getConditionFor($conditionType, $property)
    {
        if (!$this->hasCondition($property)) {
            throw new InvalidArgumentException('property don\'t have any condition');
        }

        $result = null;
        foreach ($this->conditions as $condition) {
            if ($property === $condition->getPropertyName() && $conditionType === $condition->getType()) {
                $result = $condition;
                break;
            }
        }

        return $result;
    }

    /**
     * Returns true if any condition has been set for the named property
     *
     * @param string $property
     *
     * @return boolean
     */
    public function hasCondition($property)
    {
        foreach ($this->conditions as $condition) {
            if ($condition->getPropertyName() === $property) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if any ordering has been set for the named property
     *
     * @param string $property
     *
     * @return boolean
     */
    public function hasOrdering($property)
    {
        foreach ($this->orderings as $ordering) {
            if ($ordering[0] === $property) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FilterCondition $remove
     *
     * @return FilterInterface|static
     */
    public function removeCondition(FilterCondition $remove)
    {
        foreach ($this->conditions as $key => $condition) {
            if ($condition === $remove) {
                unset($this->conditions[$key]);
            }
        }

        return $this;
    }

    /**
     * Returns true if the filter has any reference to the named property
     *
     * @param string $property
     *
     * @return boolean
     */
    public function hasReference($property)
    {
        return $this->hasCondition($property)
            || $this->hasOrdering($property);
    }

    /**
     * Returns true if the filter has any reference to one of the named properties
     *
     * @param string[] $properties
     *
     * @return boolean
     */
    public function hasAnyReference(array $properties)
    {
        return $this->hasAnyCondition($properties)
            || $this->hasAnyOrdering($properties);
    }

    /**
     * Returns true if any condition has been set for one of the named properties
     *
     * @param string[] $properties
     *
     * @return boolean
     */
    public function hasAnyCondition(array $properties)
    {
        foreach ($this->conditions as $condition) {
            if (in_array($condition->getPropertyName(), $properties, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if any ordering has been set for one of the named properties
     *
     * @param string[] $properties
     *
     * @return boolean
     */
    public function hasAnyOrdering(array $properties)
    {
        foreach ($this->orderings as $aOrdering) {
            if (in_array($aOrdering[0], $properties, true)) {
                return true;
            }
        }

        return false;
    }
}
