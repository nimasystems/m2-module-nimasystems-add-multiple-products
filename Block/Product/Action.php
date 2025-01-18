<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Block\Product;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Nimasystems\AddMultipleProducts\Helper\Data;

class Action extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Nimasystems_AddMultipleProducts::product/action.phtml';

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var Registry
     */
    protected Registry $registry;

    /**
     * @var StockRegistryInterface
     */
    protected StockRegistryInterface $stockRegistry;

    /**
     * @var ProductFactory
     */
    protected ProductFactory $productFactory;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @var Product|null
     */
    private ?Product $product = null;

    /**
     * @var Category|null
     */
    private ?Category $category = null;

    /**
     * @param Context $context
     * @param Data $dataHelper
     * @param Registry $registry
     * @param ProductFactory $productFactory
     * @param CategoryFactory $categoryFactory
     * @param StockRegistryInterface $stockRegistry
     * @param array $data
     */
    public function __construct(Template\Context       $context,
                                Data                   $dataHelper,
                                Registry               $registry,
                                ProductFactory         $productFactory,
                                CategoryFactory        $categoryFactory,
                                StockRegistryInterface $stockRegistry,
                                array                  $data = [])
    {
        parent::__construct($context, $data);

        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->stockRegistry = $stockRegistry;
    }

    protected function _toHtml(): string
    {
        if (!$this->getIsEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @param Product $product
     * @return Action
     */
    public function setProduct(Product $product): Action
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        if ($this->product) {
            return $this->product;
        }

        if (!$product = $this->registry->registry('current_product')) {
            $product = $this->getParentBlock()->getProduct();
        }

        if (!$product instanceof Product) {
            $product = $this->productFactory->create();
        }

        return $product;
    }

    /**
     * @param Category|null $category
     * @return Action
     */
    public function setCategory(?Category $category): Action
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Category
     */
    public function getCategory(): ?Category
    {
        if ($this->category) {
            return $this->category;
        }

        $category = $this->registry->registry('current_category');

        if (!$category) {
            $category = $this->getParentBlock()->getCategory();
        }

        return $category;
    }

    public function getProductId(): ?int
    {
        $product = $this->getProduct();
        /** @noinspection PhpCastIsUnnecessaryInspection */
        return $product ? (int)$product->getId() : null;
    }

    /**
     * @return string
     */
    public function getAddUrl(): string
    {
        return $this->dataHelper->getAddUrl($this->getProduct());
    }

    /**
     * @return string
     */
    public function getProductListViewJqueryContainerSelector(): string
    {
        return $this->dataHelper->getProductListViewJqueryContainerSelector();
    }

    /**
     * @return string
     */
    public function getProductViewJqueryContainerSelector(): string
    {
        return $this->dataHelper->getProductViewJqueryContainerSelector();
    }

    /**
     * @return string|null
     */
    public function getValueFormatString(): ?string
    {
        return '{value}';
    }

    public function getStockConfig(): array
    {
        $product = $this->getProduct();

        //   $stockItem = $product->getExtensionAttributes()->getStockItem();
        // TODO: is this too heavy to do for each product?
        $stockItem = $this->stockRegistry->getStockItem($product->getId());

        return [
            'usesStock' => $stockItem && $stockItem->getManageStock(),
            'inStock' => $stockItem ? $stockItem->getIsInStock() : null,
            'minSaleQty' => $stockItem ? $stockItem->getMinSaleQty() : null,
            'maxSaleQty' => $stockItem ? $stockItem->getMaxSaleQty() : null,
            'qtyUsesDecimals' => $stockItem && $stockItem->getIsQtyDecimal(),
            'qtyUsesIncrements' => $stockItem && $stockItem->getEnableQtyIncrements(),
            'qtyIncrements' => $stockItem ? $stockItem->getQtyIncrements() : null,
        ];
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return true;
    }
}
