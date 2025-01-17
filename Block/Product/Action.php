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
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Nimasystems\AddMultipleProducts\Helper\CartData;
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
     * @var CartData
     */
    protected CartData $cartDataHelper;

    /**
     * @var Registry
     */
    protected Registry $registry;

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
     * @param CartData $cartDataHelper
     * @param Registry $registry
     * @param ProductFactory $productFactory
     * @param CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(Template\Context $context,
                                Data             $dataHelper,
                                CartData         $cartDataHelper,
                                Registry         $registry,
                                ProductFactory   $productFactory,
                                CategoryFactory  $categoryFactory,
                                array            $data = [])
    {
        parent::__construct($context, $data);

        $this->dataHelper = $dataHelper;
        $this->cartDataHelper = $cartDataHelper;
        $this->registry = $registry;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
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
     * @return int
     */
    public function getProductCartQty(): int
    {
        $product = $this->getProduct();
        /** @noinspection PhpCastIsUnnecessaryInspection */
        return $product ? (int)$this->cartDataHelper->getProductCartQty((int)$product->getId()) : 0;
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
        return $this->cartDataHelper->getAddUrl($this->getProduct());
    }

    /**
     * @return string|null
     */
    public function getValueFormatString(): ?string
    {
        return '{value}';
    }

    /**
     * @return int|null
     */
    public function getMaximumQuantity(): ?int
    {
        return $this->dataHelper->getMaximumQuantity();
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return true;
    }
}

