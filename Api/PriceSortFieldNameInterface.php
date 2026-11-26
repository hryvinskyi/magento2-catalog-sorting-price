<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Api;

/**
 * Names the search-index field a listing sorted by price is ordered on for the shopper the request serves
 *
 * The price differs per customer group and website, so the field does too.
 */
interface PriceSortFieldNameInterface
{
    /**
     * Get the price field of the current shopper
     *
     * @return string Never empty
     * @throws \UnexpectedValueException When the search engine names no field for the price
     */
    public function getFieldName(): string;
}
