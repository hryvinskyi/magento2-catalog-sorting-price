<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Model;

use Hryvinskyi\CatalogSorting\Api\Data\FieldType;
use Hryvinskyi\CatalogSorting\Api\Data\SortDirection;
use Hryvinskyi\CatalogSorting\Api\Data\SortFieldInterface;
use Hryvinskyi\CatalogSorting\Api\Data\SortFieldInterfaceFactory;
use Hryvinskyi\CatalogSorting\Api\FixedDirectionShopperSortFieldInterface;
use Hryvinskyi\CatalogSortingPrice\Api\PriceSortFieldNameInterface;

/**
 * A sort code that orders by the shopper's price in one direction only, declared in `di.xml` once per direction
 *
 * The clause names the price field of the current shopper's customer group and website. A product without a price
 * in the index sorts last in either direction, as it does under the platform's own Price sort. The sort field is
 * built once per field name and kept; nothing about the shopper is read until a query sorts by the code.
 */
class PriceShopperSortField implements FixedDirectionShopperSortFieldInterface
{
    private const MISSING_LAST = '_last';

    /**
     * @var SortDirection
     */
    private readonly SortDirection $direction;

    /**
     * @var array<string, SortFieldInterface> Sort fields by price field name
     */
    private array $sortFields = [];

    /**
     * @param SortFieldInterfaceFactory $sortFieldFactory Creates the sort field of a price field
     * @param PriceSortFieldNameInterface $priceFieldName Names the current shopper's price field
     * @param string $code Sort code as the listing toolbar sends it; must not be empty or blank
     * @param string $direction `asc` or `desc`
     * @throws \InvalidArgumentException When the code is empty or blank, or the direction is not a known one
     */
    public function __construct(
        private readonly SortFieldInterfaceFactory $sortFieldFactory,
        private readonly PriceSortFieldNameInterface $priceFieldName,
        private readonly string $code,
        string $direction
    ) {
        if (trim($code) === '') {
            throw new \InvalidArgumentException('A price sort code is needed; the code is empty.');
        }

        $this->direction = SortDirection::tryFrom($direction) ?? throw new \InvalidArgumentException(sprintf(
            'Price sort code "%s" names the direction "%s", which is neither "asc" nor "desc".',
            $code,
            $direction
        ));
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function getDirection(): SortDirection
    {
        return $this->direction;
    }

    /**
     * @inheritDoc
     */
    public function getSortField(): SortFieldInterface
    {
        $fieldName = $this->priceFieldName->getFieldName();

        return $this->sortFields[$fieldName] ??= $this->sortFieldFactory->create([
            'field' => $fieldName,
            'missingValue' => self::MISSING_LAST,
            'fieldType' => FieldType::Float,
        ]);
    }
}
