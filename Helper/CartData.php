<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Helper;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class CartData extends AbstractHelper
{
    /**
     * @var Cart
     */
    protected Cart $cart;

    /**
     * @var array|null
     */
    protected ?array $cartItems = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        Cart    $cart
    )
    {
        parent::__construct($context);

        $this->cart = $cart;

        $this->loadCartItems();
    }

    /**
     * Load cart items once and cache them.
     *
     * @return array
     */
    protected function loadCartItems(): array
    {
        if ($this->cartItems === null) {
            $this->cartItems = [];
            $items = $this->cart->getQuote()->getAllItems();

            foreach ($items as $item) {
                $this->cartItems[(int)$item->getProductId()] = (int)$item->getQty();
            }
        }
        return $this->cartItems;
    }

    /**
     * Get the quantity of a product in the cart.
     *
     * @param int $productId
     * @return int|null
     */
    public function getProductCartQty(int $productId): ?int
    {
        return $this->cartItems[$productId] ?? null;
    }

    /**
     * @param $product
     * @param array $additional
     * @return string
     */
    public function getAddUrl($product, array $additional = []): string
    {
        if (isset($additional['useUencPlaceholder'])) {
            $uenc = "%uenc%";
            unset($additional['useUencPlaceholder']);
        } else {
            $uenc = $this->urlEncoder->encode($this->_urlBuilder->getCurrentUrl());
        }

        $urlParamName = ActionInterface::PARAM_NAME_URL_ENCODED;

        $routeParams = [
            $urlParamName => $uenc,
            'product' => $product ? $product->getEntityId() : null,
            '_secure' => $this->_getRequest()->isSecure(),
        ];

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product && $product->hasUrlDataObject()) {
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_scope_to_url'] = true;
        }

        if ($this->_getRequest()->getRouteName() == 'nimasystems_amp'
            && $this->_getRequest()->getControllerName() == 'cart'
        ) {
            $routeParams['in_cart'] = 1;
        }

        return $this->_getUrl('nimasystems_amp/cart/add', $routeParams);
    }
}
