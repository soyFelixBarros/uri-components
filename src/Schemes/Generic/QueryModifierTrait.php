<?php
/**
 * League.Url (http://url.thephpleague.com)
 *
 * @link      https://github.com/thephpleague/uri/
 * @copyright Copyright (c) 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.0.0
 * @package   League.uri
 */
namespace League\Uri\Schemes\Generic;

use League\Uri\Interfaces\Components\Collection;

/**
 * common URI Object query properties modifiers
 *
 * @package League.uri
 * @since   4.0.0
 */
trait QueryModifierTrait
{
    /**
     * Query Component
     *
     * @var QueryInterface
     */
    protected $query;

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        return $this->withProperty('query', $query);
    }

    /**
     * {@inheritdoc}
     */
    abstract protected function withProperty($name, $value);

    /**
     * {@inheritdoc}
     */
    public function mergeQuery($query)
    {
        return $this->withProperty('query', $this->query->merge($query));
    }

    /**
     * {@inheritdoc}
     */
    public function ksortQuery($sort = SORT_REGULAR)
    {
        return $this->withProperty('query', $this->query->ksort($sort));
    }

    /**
     * {@inheritdoc}
     */
    public function withoutQueryValues($offsets)
    {
        return $this->withProperty('query', $this->query->without($offsets));
    }

    /**
     * {@inheritdoc}
     */
    public function filterQuery(callable $callable, $flag = Collection::FILTER_USE_VALUE)
    {
        return $this->withProperty('query', $this->query->filter($callable, $flag));
    }
}