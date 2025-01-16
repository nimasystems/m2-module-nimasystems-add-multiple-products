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
use Nimasystems\AddMultipleProducts\Helper\CartData;

class Action extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Nimasystems_AddMultipleProducts::product/action.phtml';

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

    public function __construct(Template\Context $context,
                                CartData         $cartDataHelper,
                                Registry         $registry,
                                ProductFactory   $productFactory,
                                CategoryFactory  $categoryFactory,
                                array            $data = [])
    {
        parent::__construct($context, $data);

        $this->cartDataHelper = $cartDataHelper;
        $this->registry = $registry;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
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
     * @return Category
     */
    public function getCategory(): ?Category
    {
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

    public function isProductListing(): bool
    {
        $req_name = $this->getRequest()->getFullActionName();

        return ($this->getCategory() != null ||
                $req_name == 'catalogsearch_result_index') &&
            $req_name != 'catalog_product_view';
    }
}

