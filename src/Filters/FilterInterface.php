<?php

namespace PhpPond\Filters;


/**
 * Interface FilterInterface
 *
 * @package PhpPondFilters\Filters
 */
interface FilterInterface
{

    /**
     * get $qb->select(....)
     *
     * @return mixed
     */
    public function getSelect();

    /**
     * @return FilterInterface
     */
    public function first();

    /**
     * Set the start index
     *
     * @param int $start
     *
     * @return FilterInterface
     */
    public function setStart($start = null);

    /**
     * Set the limit on the number of objects to return
     *
     * @param int $limit
     *
     * @return FilterInterface
     */
    public function setLimit($limit = null);

    /**
     * @return int|null
     */
    public function getStart();

    /**
     * @return int|null
     */
    public function getLimit();

    /**
     * Set a property to order the result set by. This method should be able to be called more than once
     * and append the ordering to the list retrieved by getOrderings()
     *
     * @param string $property
     * @param bool   $isAscending
     *
     * @return FilterInterface
     */
    public function addOrdering($property, $isAscending = true);

    /**
     * @return FilterInterface
     */
    public function clearOrderings();

    /**
     * Returns an array of orderings array(propertyName, ascending)

     * @return array<string,boolean>
     */
    public function getOrderings();

    /**
     * Add a FilterCondition
     * This method can be called multiple times. Successive conditions are ANDed
     *
     * @param FilterCondition $condition
     *
     * @return FilterInterface
     */
    public function addCondition(FilterCondition $condition);

    /**
     * @return FilterInterface
     */
    public function clearConditions();

    /**
     * Removes Condition for appropriate property and type
     *
     * @param FilterCondition $condition
     *
     * @return FilterInterface
     */
    public function removeCondition(FilterCondition $condition);

    /**
     * Returns an array of FilterConditions
     *
     * @return FilterCondition[]
     */
    public function getConditions();

    /**
     * Returns true if one or more conditions has been set for the named property
     *
     * @param string $conditionType
     * @param string $property
     *
     * @return boolean
     */
    public function hasConditionFor($conditionType, $property);

    /**
     * Returns the first condition of the given type on the named property
     *
     * @param string $conditionType
     * @param string $property
     *
     * @return FilterCondition
     */
    public function getConditionFor($conditionType, $property);

}
