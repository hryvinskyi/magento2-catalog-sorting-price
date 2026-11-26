<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Model;

use Hryvinskyi\CatalogSortingPrice\Api\PriceSortFieldNameInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;

/**
 * Asks the search engine's own field-name resolver for the price field, as the platform's Price sort does
 *
 * The platform's Price sort is rendered on the field this resolver names for the `price` attribute (for the current
 * customer group and website), so both price directions of this module order on exactly the same field.
 */
class PlatformPriceSortFieldName implements PriceSortFieldNameInterface
{
    private const PRICE_ATTRIBUTE = 'price';

    /**
     * @param AttributeProvider $attributeProvider Gives the search adapter's view of a product attribute
     * @param ResolverInterface $fieldNameResolver Names an attribute's field in the product search index
     */
    public function __construct(
        private readonly AttributeProvider $attributeProvider,
        private readonly ResolverInterface $fieldNameResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFieldName(): string
    {
        $fieldName = $this->fieldNameResolver->getFieldName(
            $this->attributeProvider->getByAttributeCode(self::PRICE_ATTRIBUTE)
        );

        if ($fieldName === null || $fieldName === '') {
            throw new \UnexpectedValueException('The search engine names no index field for the price.');
        }

        return $fieldName;
    }
}
