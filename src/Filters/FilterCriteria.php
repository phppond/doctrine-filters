<?php

namespace PhpPond\Filters;


use Doctrine\ORM\QueryBuilder;

use PhpPond\ORM\CriteriaInterface;

/**
 * Class OrmFilterCriteria
 *
 * @package PhpPondFilters\Filters
 * @author nick
 */
class FilterCriteria implements CriteriaInterface
{

    /** @var FilterInterface */
    private $filter;

    /** @var array<property,map>  */
    private $properties;

    /**
     * OrmFilterCriteria constructor.
     *
     * @param FilterInterface $filter
     * @param array           $properties
     */
    public function __construct(FilterInterface $filter, $properties)
    {
        $this->filter = $filter;
        $this->properties = $properties;
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    public function apply(QueryBuilder $queryBuilder)
    {
        $compiler = new SqlFilterCompiler();
        // @codingStandardsIgnoreStart
        // $compiler->setSkipMissingProperties(true);
        // @codingStandardsIgnoreEnd

        $compiler->compile($this->properties, $this->filter);

        $select = $compiler->getSelect(false) AND $queryBuilder->addSelect($select);
        $where = $compiler->getWhere(false) AND $queryBuilder->andWhere($where);

        $having = $compiler->getHaving(false) AND $queryBuilder->andHaving($having);
        $order = $compiler->getOrder(false) AND $queryBuilder->addOrderBy($order);

        foreach ($compiler->getParameters() as $key => $value) {
            $queryBuilder->setParameter($key, $value);
        }
    }
}
