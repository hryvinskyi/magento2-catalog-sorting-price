# Magento 2 Catalog Sorting: Price Directions

Adds **Price: Low to High** and **Price: High to Low** to the storefront Sort By list: two entries ordered by the shopper's price, each always in its own direction, per store view.

## What it does for a store

- **Two price sort orders** (`product_list_order=price_asc` and `product_list_order=price_desc`), offered in the store views where they are enabled. A shopper picks the direction from the list itself, not with the direction arrow.
- **Each entry keeps its direction.** `price_asc` is always cheapest first and `price_desc` always dearest first, whatever direction the request carries.
- **The platform's own price field.** The engine orders on the field the platform's Price sort uses: the price of the shopper's customer group on the store's website. A product without a price in the index sorts last in both directions, as under the platform's Price sort.
- **Nothing to index, nothing per request.** The price is already in every product search document; the module adds no field, no table and no refresh.

## Requirements

- PHP 8.1 to 8.5.
- `hryvinskyi/magento2-catalog-sorting` ^1.1 and `hryvinskyi/magento2-catalog-sorting-options` ^1.0.
- The platform's Elasticsearch, Store, Config and Backend modules (what `composer.json` and `etc/module.xml` declare).

## Installation

```bash
composer require hryvinskyi/magento2-catalog-sorting-price
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

**Stores › Configuration › Catalog › Catalog Sorting › Price Directions**

| Path | Label | Default | Scope |
|---|---|---|---|
| `catalog_sorting/price/enabled` | Enabled | No | default, website, store view |

The setting offers both entries. Their labels, their places and the visibility of the platform's own **Price** entry are set in **Catalog Sorting › Sort Options**: hide Price there to leave only the two directions.

## Limits

- The direction arrow of the listing toolbar still changes the URL's direction for these entries, but not their order.

## Registrations

| Pool | Item |
|---|---|
| `sortFields` of `Hryvinskyi\CatalogSorting\Model\Search\ShopperSortFields` | `price_asc` and `price_desc`, virtual types of `Model\PriceShopperSortField`, ascending and descending |
| `options` of `Hryvinskyi\CatalogSortingOptions\Model\ListingSortOptions` | option `price_asc`, "Price: Low to High", ascending, `sortOrder` 170; option `price_desc`, "Price: High to Low", descending, `sortOrder` 180 |
