<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Controller\Cart;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Controller\Cart;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Add extends Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var LayoutInterface
     */
    protected LayoutInterface $layout;

    /**
     * @var LocalizedToNormalized
     */
    protected LocalizedToNormalized $localizedToNormalized;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var ResolverInterface
     */
    protected ResolverInterface $resolverInterface;

    /**
     * @var Escaper
     */
    protected Escaper $escaper;

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Add constructor.
     * @param Context $context
     * @param LayoutInterface $layout
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param LocalizedToNormalized $localizedToNormalized
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param JsonFactory $resultJsonFactory
     * @param ResolverInterface $resolverInterface
     * @param Data $dataHelper
     * @param Escaper $escaper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context                    $context,
        LayoutInterface            $layout,
        ScopeConfigInterface       $scopeConfig,
        Session                    $checkoutSession,
        StoreManagerInterface      $storeManager,
        Validator                  $formKeyValidator,
        LocalizedToNormalized      $localizedToNormalized,
        CustomerCart               $cart,
        ProductRepositoryInterface $productRepository,
        JsonFactory                $resultJsonFactory,
        ResolverInterface          $resolverInterface,
        Data                       $dataHelper,
        Escaper                    $escaper,
        LoggerInterface            $logger
    )
    {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->layout = $layout;
        $this->localizedToNormalized = $localizedToNormalized;
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resolverInterface = $resolverInterface;
        $this->dataHelper = $dataHelper;
        $this->escaper = $escaper;
        $this->logger = $logger;
    }

    /**
     * @return ProductInterface|null
     * @throws NoSuchEntityException
     */
    protected function _initProduct(): ?ProductInterface
    {
        $productId = (int)$this->getRequest()->getParam('product');

        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();

            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * @return Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParams();

        // Initialize product
        $product = $this->_initProduct();

        if (!$product) {
            return $this->_goBack();
        }

        // TODO: validate - check min,max,qty decimals,qty increments quantity

        // Allow to customize and filter/disable the action based on other criteria
        $response = new DataObject(['can_execute' => true,
                                    'error_message' => __('Quantity for product %1 cannot be changed.', $product->getName())]);

        $this->_eventManager->dispatch(
            'checkout_cart_validate_add_product',
            ['product' => $product, 'request' => $this->getRequest(), 'response' => $response]
        );

        // Check if execution is allowed
        if (!$response->getData('can_execute')) {
            $errorMessage = $response->getData('error_message');
            $this->messageManager->addErrorMessage($errorMessage);
            return $resultJson->setData(['success' => false, 'error' => $errorMessage]);
        }

        $quote = $this->cart->getQuote();

        try {
            // Get the existing cart item by product
            /** @noinspection PhpParamsInspection */
            $cartItem = $quote->getItemByProduct($product);

            if ($cartItem) {
                $exact = $params['exact'] ?? false;

                // Update the quantity
                $newQty = (!$exact ? $cartItem->getQty() : 0) + (isset($params['qty']) ? (double)$params['qty'] : -1); // TODO: -1 - check increments here

                // Ensure quantity doesn't go below 1
                if ($newQty < 1) {
                    $this->cart->removeItem($cartItem->getId()); // Remove the item if quantity is less than 1
                } else {
                    $cartItem->setQty($newQty); // Update the quantity
                }
            } else {
                // Add product to cart if it doesn't already exist
                if (isset($params['qty'])) {
                    $filter = $this->localizedToNormalized->setOptions(
                        ['locale' => $this->resolverInterface->getLocale()]
                    );
                    $params['qty'] = $filter->filter($params['qty']);
                }

                /** @noinspection PhpParamsInspection */
                $this->cart->addProduct($product, $params);

                /** @noinspection PhpParamsInspection */
                $cartItem = $quote->getItemByProduct($product);
            }

            if (!$cartItem) {
                throw new Exception('Cart item not found');
            }

            // Save the cart
            $this->cart->save();

            // Dispatch an event after the cart is updated
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            // TODO: enable this with a separate configuration
//            $this->messageManager->addSuccessMessage(
//                __('You updated the quantity of %1 in your shopping cart.', $product->getName())
//            );

            $rowTotal = $cartItem->getRowTotal();

            return $resultJson->setData([
                'success' => true,
                'cart' => [
                    'item' => [
                        'price' => $rowTotal,
                        'priceRowTotal' => $this->dataHelper->formatPrice($rowTotal),
                    ],
                ],
            ]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultJson->setData(['success' => false, 'error' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We cannot update your shopping cart right now.'));
            $this->logger->critical($e);
            return $resultJson->setData(['success' => false]);
        }
    }
}
