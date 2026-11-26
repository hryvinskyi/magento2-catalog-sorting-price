<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Test\Integration;

use Hryvinskyi\CatalogSortingOptions\Api\ListingSortOptionsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Search\Request\Builder;
use Magento\Framework\Search\RequestInterface;
use Magento\OpenSearch\SearchAdapter\Mapper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The two price codes on the real wiring: a storefront category listing sorted by either reaches the engine ordered
 * on the very field the platform's own Price sort uses, in the code's direction whatever direction the request
 * carries, and both options are offered, each opening in its own direction, where the store enables them.
 */
#[AppArea('frontend')]
class PriceSortTest extends TestCase
{
    private const REQUEST_NAME = 'catalog_view_container';
    private const STORE_CODE = 'default';
    private const VISIBLE_IN_CATALOG_AND_SEARCH = [2, 4];
    private const ROOT_CATEGORY = 2;

    /**
     * @return void
     */
    #[AppIsolation(true)]
    public function testEachPriceCodeIsOrderedOnThePlatformsPriceFieldInItsOwnDirection(): void
    {
        $priceField = $this->platformPriceField();

        $lowestFirst = $this->sortOfTheEngineQuery('price_asc', 'DESC');
        self::assertContains(
            [$priceField => ['order' => 'asc', 'missing' => '_last', 'unmapped_type' => 'float']],
            $lowestFirst
        );
        self::assertSame([], $this->clausesKeyedBy('price_asc', $lowestFirst));

        $highestFirst = $this->sortOfTheEngineQuery('price_desc', 'ASC');
        self::assertContains(
            [$priceField => ['order' => 'desc', 'missing' => '_last', 'unmapped_type' => 'float']],
            $highestFirst
        );
        self::assertSame([], $this->clausesKeyedBy('price_desc', $highestFirst));
    }

    /**
     * @return void
     */
    #[
        AppIsolation(true),
        Config('catalog_sorting/price/enabled', '1', 'store', self::STORE_CODE)
    ]
    public function testBothOptionsAreOfferedEachInItsOwnDirectionWhereTheStoreEnablesThem(): void
    {
        $options = $this->service(ListingSortOptionsInterface::class);
        $offered = $options->offeredIn($this->storeId());

        self::assertSame('Price: Low to High', $offered['price_asc'] ?? null);
        self::assertSame('Price: High to Low', $offered['price_desc'] ?? null);
        self::assertSame('asc', $options->defaultDirectionOf('price_asc')?->value);
        self::assertSame('desc', $options->defaultDirectionOf('price_desc')?->value);
    }

    /**
     * Get the field the platform's own Price sort orders on, from its clause in an engine query sorted by price
     *
     * The platform renders that clause with nothing but a direction, which tells it from the clauses of leading rules
     * and from the relevance clause.
     *
     * @return string
     */
    private function platformPriceField(): string
    {
        $fields = [];
        foreach ($this->sortOfTheEngineQuery('price', 'ASC') as $clause) {
            $field = is_array($clause) ? array_key_first($clause) : null;
            if (is_string($field) && $field !== '_score' && $clause[$field] === ['order' => 'asc']) {
                $fields[] = $field;
            }
        }
        self::assertCount(1, $fields);

        return $fields[0];
    }

    /**
     * Get the clauses keyed by the given key
     *
     * @param string $key Clause key
     * @param array<mixed> $sort Engine sort clauses
     * @return array<mixed>
     */
    private function clausesKeyedBy(string $key, array $sort): array
    {
        return array_values(array_filter(
            $sort,
            static fn (mixed $clause): bool => is_array($clause) && array_key_first($clause) === $key
        ));
    }

    /**
     * Build the category listing request sorted by a code and get the sort of the engine query for it
     *
     * @param string $code Sort code as the listing passes it
     * @param string $direction Direction as the listing passes it
     * @return array<mixed>
     */
    private function sortOfTheEngineQuery(string $code, string $direction): array
    {
        $query = $this->newInstance(Mapper::class)->buildQuery($this->listingRequest($code, $direction));
        $body = $query['body'] ?? null;
        self::assertIsArray($body);
        $sort = $body['sort'] ?? null;
        self::assertIsArray($sort);

        return $sort;
    }

    /**
     * Build the storefront category listing request, sorted by a code
     *
     * @param string $code Sort code as the listing passes it
     * @param string $direction Direction as the listing passes it
     * @return RequestInterface
     */
    private function listingRequest(string $code, string $direction): RequestInterface
    {
        $builder = $this->newInstance(Builder::class);
        $builder->setRequestName(self::REQUEST_NAME);
        $builder->bindDimension('scope', (string)$this->storeId());
        $builder->bind('category_ids', self::ROOT_CATEGORY);
        $builder->bind('visibility', self::VISIBLE_IN_CATALOG_AND_SEARCH);
        $builder->setFrom(0);
        $builder->setSize(12);
        $sortOrder = $this->newInstance(SortOrder::class);
        $sortOrder->setField($code);
        $sortOrder->setDirection($direction);
        $builder->setSort([$sortOrder]);

        return $builder->create();
    }

    /**
     * Get the id of the default store view
     *
     * @return int
     */
    private function storeId(): int
    {
        return (int)$this->service(StoreManagerInterface::class)->getStore(self::STORE_CODE)->getId();
    }

    /**
     * Get a shared service from the real container, checked to be of the requested type
     *
     * @template T of object
     * @param class-string<T> $type Interface or class to resolve
     * @return T
     */
    private function service(string $type): object
    {
        $service = Bootstrap::getObjectManager()->get($type);
        if (!$service instanceof $type) {
            throw new \LogicException(sprintf('The container returned a service that is not a %s.', $type));
        }

        return $service;
    }

    /**
     * Create a new instance through the real container, checked to be of the requested type
     *
     * @template T of object
     * @param class-string<T> $type Class to create
     * @return T
     */
    private function newInstance(string $type): object
    {
        $instance = Bootstrap::getObjectManager()->create($type);
        if (!$instance instanceof $type) {
            throw new \LogicException(sprintf('The container created an object that is not a %s.', $type));
        }

        return $instance;
    }
}
