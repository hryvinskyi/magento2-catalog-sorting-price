<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Test\Unit\Model;

use Hryvinskyi\CatalogSortingPrice\Model\PlatformPriceSortFieldName;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeAdapter;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * A search engine that names no field for the price fails the price sort loudly instead of sorting on an empty field
 */
class PlatformPriceSortFieldNameTest extends TestCase
{
    /**
     * @return void
     */
    public function testFailsWhenTheSearchEngineNamesNoPriceField(): void
    {
        $attributeProvider = $this->createMock(AttributeProvider::class);
        $attributeProvider->method('getByAttributeCode')->willReturn($this->createMock(AttributeAdapter::class));
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('getFieldName')->willReturn(null);

        $this->expectException(\UnexpectedValueException::class);

        (new PlatformPriceSortFieldName($attributeProvider, $resolver))->getFieldName();
    }
}
