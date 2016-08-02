<?php

namespace PhpPond\Filters;


use InvalidArgumentException;

/**
 * Class SqlFilterCompiler
 *
 * @package PhpPondFilters\Interfaces
 *
 * @author  Nick G. Lavrik <nick.lavrik@gmail.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SqlFilterCompiler implements FilterCompilerInterface
{

    /** @var  array */
    protected $where;
    /** @var  array */
    protected $order;
    /** @var  int */
    protected $limit;
    /** @var  array */
    protected $having;

    /** @var array  */
    protected $parameters = [ ];
    /** @var string  */
    protected $parameterPrefix = 'p';

    /** @var array  */
    protected $select = [];

    /** @var array */
    protected $propertyMap;

    /**
     * Allows us to compile filters which refer to non-existent properties
     * This is useful in some edge cases (.e.g see ModerationDao's use with UNION queries)
     *
     * @var boolean
     */
    protected $skipMissingProperties = false;

    /**
     * @param array           $propertyMap
     * @param FilterInterface $filter
     *
     * @throws InvalidArgumentException
     */
    public function compile(array $propertyMap, FilterInterface $filter)
    {
        // SELECT $fieldList FROM $tableNames WHERE [conditions] ORDER BY [orderings] [limit]
        // SELECT COUNT(*) FROM $tableNames WHERE [conditions] ORDER BY [orderings] [limit]
        // Calculate the WHERE clause
        $this->reset($propertyMap);
        $this->compileFilterSelect($filter);
        $this->compileFilterConditions($filter);
        $this->compileFilterOrderings($filter);
        $this->compileFilterLimit($filter);
    }

    /**
     * @param array $propertyMap
     */
    protected function reset(array $propertyMap)
    {
        $this->where = '';
        $this->order = '';
        $this->limit = '';
        $this->parameters = [];
        $this->select = [];
        $this->having = '';

        $this->propertyMap = $propertyMap;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    private function isSkipProperty($property)
    {
        return (bool) $this->skipMissingProperties
            && !array_key_exists($property, $this->propertyMap);
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterSelect(FilterInterface $filter)
    {
        foreach ($filter->getSelect() as $alias => $property) {
            if ($this->isSkipProperty($property)) {
                continue;
            }
            $this->compileSelect($property, $alias);
        }
    }

    /**
     * @param string $property
     * @param string $alias
     */
    protected function compileSelect($property, $alias)
    {
        $select = $this->getMappedProperty($property);
        $this->select[] = $select . ' AS `' . $alias . '`';
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterConditions(FilterInterface $filter)
    {
        $conjunction = '';

        foreach ($filter->getConditions() as $condition) {
            if ($this->isSkipProperty($condition->getPropertyName())) {
                continue;
            }

            if ($condition->isGroup()) {
                $this->compileGroup($condition, $conjunction);
                if (count($condition->getConditions())) {
                    $conjunction = ' AND ';
                }
            } else {
                $this->compileCondition($condition, $conjunction);
                $conjunction = ' AND ';
            }
        }
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterOrderings(FilterInterface $filter)
    {
        foreach ($filter->getOrderings() as $ordering) {
            list($property, $isAscending) = $ordering;

            if ($this->isSkipProperty($property)) {
                continue;
            }

            $this->compileOrdering($property, $isAscending);
        }
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterLimit(FilterInterface $filter)
    {
        $start = $filter->getStart();
        $limit = $filter->getLimit();

        if ($start !== null && $start > 0) {
            if ($limit === null) {
                throw new InvalidArgumentException('A limit value must be supplied when start is set');
            }
            $this->limit = $start.', '.$limit;
        } else {
            if ($limit !== null) {
                $this->limit = $limit;
            }
        }
    }

    /**
     * @param boolean $isSkip
     */
    public function setSkipMissingProperties($isSkip)
    {
        $this->skipMissingProperties = $isSkip;
    }

    /**
     * @param string $prefix
     */
    public function setParameterPrefix($prefix = 'p')
    {
        $this->parameterPrefix = $prefix;
    }

    /**
     * @return string
     */
    public function getParameterPrefix()
    {
        return $this->parameterPrefix;
    }

    /**
     * Returns the select fields as a comma-separated string
     *
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getSelect($withKeyword = true)
    {
        if (empty($this->select)) {
            return '';
        }

        return $withKeyword ? 'SELECT ' . implode(', ', $this->select) : $this->select;
    }

    /**
     * @param boolean $withKeyword
     *
     * @return string The compiled query which can be run to get a count of the number of rows
     */
    public function getWhere($withKeyword = true)
    {
        if (empty($this->where)) {
            return '';
        }

        return $withKeyword ? 'WHERE ' . $this->where : $this->where;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getOrder($withKeyword = true)
    {
        if (empty($this->order)) {
            return '';
        }

        return $withKeyword ? 'ORDER BY ' . $this->order : $this->order;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getLimit($withKeyword = true)
    {
        if (empty($this->limit)) {
            return '';
        }

        return $withKeyword ? 'LIMIT ' . $this->limit : $this->limit;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getHaving($withKeyword = true)
    {
        if (empty($this->having)) {
            return '';
        }

        return $withKeyword ? 'HAVING ' . $this->having : $this->having;
    }

    /**
     * Return an array of key/value pairs to bind to the prepared query
     * array(array(key,value))
     *
     * @return array<array<string>>
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param FilterCondition $condition
     * @param string          $conjunction
     */
    protected function compileGroup(FilterCondition $condition, $conjunction)
    {
        if (!count($condition->getConditions())) {
            return;
        }

        $this->where .= $conjunction . '(';
        $or = '';
        foreach ($condition->getConditions() as $orCondition) {
            $this->compileCondition($orCondition, $or);
            $or = ' OR ';
        }
        $this->where .= ')';
    }

    /**
     * @param FilterCondition $condition
     * @param string          $conjunction
     *
     * @throws InvalidArgumentException
     */
    protected function compileCondition(FilterCondition $condition, $conjunction)
    {
        $where = $this->compileInnerCondition($condition);

        if (!empty($where)) {
            $this->where .= $conjunction;

            if ($condition->isNegated()) {
                $this->where .= 'NOT (' . $where . ')';
            } else {
                $this->where .= $where;
            }
        }
    }

    /**
     * @param FilterCondition $condition
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileInnerCondition(FilterCondition $condition)
    {
        $mappedPropertyName = $this->getMappedProperty($condition->getPropertyName());

        return $this->compileMappedCondition($condition, $mappedPropertyName);
    }

    /**
     * @param FilterCondition $condition
     * @param string          $mappedName
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileMappedCondition(FilterCondition $condition, $mappedName)
    {

        $value = $condition->getValue();
        $where = '';

        if ($condition->isLike()) {
            $where .= $this->compileLikeCondition($mappedName, $value);
        }

        if ($condition->isEquals()) {
            $where .= $this->compileEqualsCondition($mappedName, $value);
        }

        if ($condition->isGT()) {
            $where .= $this->compileScalarCondition($mappedName, '>', $value);
        }

        if ($condition->isGTE()) {
            $where .= $this->compileScalarCondition($mappedName, '>=', $value);
        }

        if ($condition->isLT()) {
            $where .= $this->compileScalarCondition($mappedName, '<', $value);
        }

        if ($condition->isLTE()) {
            $where .= $this->compileScalarCondition($mappedName, '<=', $value);
        }

        if ($condition->isIn()) {
            $where .= $this->compileInCondition($mappedName, (array) $value);
        }

        return $where;
    }

    /**
     * @param string $mappedName
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileLikeCondition($mappedName, $value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('NULL is not allowed for a LIKE filter');
        }

        return $mappedName . ' LIKE ' . $this->pushLikeParameter($value);
    }

    /**
     * @param string $mappedName
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileEqualsCondition($mappedName, $value)
    {
        $where = '';
        if ($value === null) {
            $where .= $mappedName . ' IS NULL ';
        } else {
            $where .= $this->compileScalarCondition($mappedName, '=', $value);
        }

        return $where;
    }

    /**
     * @param string $mappedName
     * @param array  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileInCondition($mappedName, array $value)
    {
        $where = '';
        $where .= $mappedName . ' IN (';
        $sComma = '';
        foreach ($value as $mInValue) {
            $where .= $sComma . $this->pushParameter($mInValue);
            $sComma = ', ';
        }
        $where .= ')';

        return $where;
    }

    /**
     * @param string $mappedName
     * @param string $condition
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileScalarCondition($mappedName, $condition, $value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('NULL is not allowed for a "' . $condition . '" filter');
        }

        /*
         * ToDo: how we can detect is $value can be resolved (scalar OR DateTime)
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('A scalar is expected for a "' . $condition . '" filter');
        }
        */

        return $mappedName . ' ' . $condition . ' ' . $this->pushParameter($value);
    }

    /**
     * @param string $property
     * @param bool   $isAscending
     *
     * @throws InvalidArgumentException
     */
    public function compileOrdering($property, $isAscending)
    {
        $mappedName = $this->getMappedProperty($property);
        $this->appendOrdering($mappedName, $isAscending);
    }

    /**
     * @param string  $mappedName
     * @param boolean $isAscending
     */
    protected function appendOrdering($mappedName, $isAscending)
    {
        $comma = '';
        if (!empty($this->order)) {
            $comma = ',';
        }
        $this->order .= $comma . $mappedName . ' ' . ($isAscending ? 'ASC' : 'DESC');
    }

    /**
     * @param $property
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function getMappedProperty($property)
    {
        if (!array_key_exists($property, $this->propertyMap)) {
            throw new InvalidArgumentException('Unknown property "' . $property . '"');
        }

        return $this->propertyMap[$property];
    }

    /**
     * Push the parameter to the array and return the identifier to use for it
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function pushParameter($value)
    {

        if ($value === null) {
            return 'NULL';
        }

        $id = $this->getParameterPrefix() . count($this->parameters);
        $this->parameters[$id] = $value;

        return ':' . $id;
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function pushLikeParameter($value)
    {
        $value = str_replace('*', '%', $value);

        return $this->pushParameter($value);
    }

    /**
     * @param string $field
     */
    public function addSelectField($field)
    {
        $this->select[] = $field;
    }

    /**
     * @param string $having
     */
    public function addHaving($having)
    {
        if (!empty($this->having)) {
            $this->having .= ' AND ';
        }
        $this->having .= $having;
    }

    /**
     * If you have a pseudo-property which maps to multiple real columns
     * then you can use this method to compile the condition
     * Only EQUALS or LIKE can be used with grouped columns
     *
     * @param FilterCondition $condition
     * @param string[]        $properties
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function compileTextGroupCondition(FilterCondition $condition, array $properties)
    {
        $mappedProperties = array();
        foreach ($properties as $property) {
            $mappedProperties[] = $this->getMappedProperty($property);
        }

        return $this->_compileTextGroupCondition($condition, $mappedProperties);
    }

    /**
     * If you have a pseudo-property which maps to multiple real columns
     * then you can use this method to compile the condition
     * Only EQUALS or LIKE can be used with grouped columns
     *
     * @param FilterCondition $condition
     * @param string[]        $mappedProperties
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function _compileTextGroupCondition(FilterCondition $condition, array $mappedProperties)
    {
        // We'll allow equals or like for this condition
        if (!($condition->isEquals() || $condition->isLike())) {
            throw new InvalidArgumentException('Group filters only allowed with EQUALS or LIKE');
        }
        $value = $condition->getValue();

        $where = ' (';
        $or = '';

        foreach ($mappedProperties as $property) {
            if ($condition->isEquals()) {
                $where .= $or . $property . '=' . $this->pushParameter($value);
            } else {
                $where .= $or . $property . ' LIKE (' . $this->pushLikeParameter($value) . ')';
            }
            $or = ' OR ';
        }

        $where .= ') ';

        return $where;
    }

    /**
     * @param boolean  $isAscending
     * @param string[] $properties
     */
    protected function compileTextGroupOrdering($isAscending, array $properties)
    {
        foreach ($properties as $property) {
            $this->compileOrdering($property, $isAscending);
        }
    }
}
