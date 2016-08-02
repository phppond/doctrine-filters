<?php

namespace PhpPond\Interfaces;


/**
 * Interface FilterCompilerInterface
 *
 * @package PhpPondFilters\Interfaces
 */
interface FilterCompilerInterface
{

    /**
     * @param string[]        $propertyMap
     * @param FilterInterface $filter
     */
    public function compile(array $propertyMap, FilterInterface $filter);

    /**
     * @return string|array
     */
    public function getSelect();

    /**
     * @return string The compiled query which can be run to get a count of the number of rows
     */
    public function getWhere();

    /**
     * @return string
     */
    public function getOrder();

    /**
     * @return string
     */
    public function getLimit();

    /**
     * Return an array of key/value pairs to bind to the prepared query
     * array(array(key,value))
     *
     * @return array<array<string>>
     */
    public function getParameters();

}
