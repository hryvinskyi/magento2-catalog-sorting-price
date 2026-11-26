<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\CatalogSortingPrice\Test\Unit\Model;

use Hryvinskyi\CatalogSorting\Api\Data\FieldType;
use Hryvinskyi\CatalogSorting\Api\Data\SortFieldInterfaceFactory;
use Hryvinskyi\CatalogSorting\Model\Data\SortField;
use Hryvinskyi\CatalogSortingPrice\Api\PriceSortFieldNameInterface;
use Hryvinskyi\CatalogSortingPrice\Model\PriceShopperSortField;
use PHPUnit\Framework\TestCase;

/**
 * A price code sorts on the price field of the shopper the request serves, with products without a price last, and a
 * registration naming a direction the engine does not know fails when it is built
 */
class PriceShopperSortFieldTest extends TestCase
{
    /**
     * @return void
     */
    public function testSortsOnThePriceFieldOfTheShopperTheRequestServesWithUnpricedProductsLast(): void
    {
        $fieldName = $this->createMock(PriceSortFieldNameInterface::class);
        $fieldName->method('getFieldName')->willReturnOnConsecutiveCalls('price_0_1', 'price_1_1', 'price_0_1');
        $sortField = $this->sortField($fieldName, 'asc');

        $guest = $sortField->getSortField();
        $wholesale = $sortField->getSortField();
        $guestAgain = $sortField->getSortField();

        self::assertSame(['price_0_1', '_last', FieldType::Float], $this->describe($guest));
        self::assertSame(['price_1_1', '_last', FieldType::Float], $this->describe($wholesale));
        self::assertSame(['price_0_1', '_last', FieldType::Float], $this->describe($guestAgain));
    }

    /**
     * @return void
     */
    public function testRefusesADirectionTheEngineDoesNotKnowNamingTheCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price sort code "price_asc" names the direction "ASC"');

        $this->sortField($this->createMock(PriceSortFieldNameInterface::class), 'ASC');
    }

    /**
     * Build the price sort field of the code `price_asc` over a factory that creates real sort fields
     *
     * @param PriceSortFieldNameInterface $fieldName Names the shopper's price field
     * @param string $direction Direction as di.xml passes it
     * @return PriceShopperSortField
     */
    private function sortField(PriceSortFieldNameInterface $fieldName, string $direction): PriceShopperSortField
    {
        $factory = $this->createMock(SortFieldInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            static function (array $data): SortField {
                $field = $data['field'] ?? null;
                $missingValue = $data['missingValue'] ?? null;
                $fieldType = $data['fieldType'] ?? null;
                if (!is_string($field) || !is_scalar($missingValue) || !$fieldType instanceof FieldType) {
                    throw new \LogicException('The sort field was asked for with keys the contract does not name.');
                }

                return new SortField($field, $missingValue, $fieldType);
            }
        );

        return new PriceShopperSortField($factory, $fieldName, 'price_asc', $direction);
    }

    /**
     * Describe a sort field as its field, missing value and type
     *
     * @param \Hryvinskyi\CatalogSorting\Api\Data\SortFieldInterface $sortField Sort field
     * @return array{string, int|float|string|bool, FieldType}
     */
    private function describe(\Hryvinskyi\CatalogSorting\Api\Data\SortFieldInterface $sortField): array
    {
        return [$sortField->getField(), $sortField->getMissingValue(), $sortField->getFieldType()];
    }
}
