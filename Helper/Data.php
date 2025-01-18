<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\AddMultipleProducts\Helper;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const MODULE_NAME = 'Nimasystems_AddMultipleProducts';

    const XML_PATH = 'nimasystems_add_multiple_products/';
    const XML_PATH_SETTINGS = self::XML_PATH . 'settings/';
    const XML_PATH_PRODUCT_LIST_VIEW = self::XML_PATH . 'product_list_view/';
    const XML_PATH_PRODUCT_VIEW = self::XML_PATH . 'product_view/';

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ModuleListInterface
     */
    protected ModuleListInterface $moduleList;

    /**
     * @var Url
     */
    protected Url $urlHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param Url $urlHelper
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface   $moduleList,
        Url                   $urlHelper
    )
    {
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->urlHelper = $urlHelper;

        parent::__construct($context);
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return mixed
     */
    public function getStoreConfig(string $code, string $path = self::XML_PATH, int $storeId = null)
    {
        return $this->scopeConfig->getValue($path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return bool
     */
    public function getStoreConfigFlag(string $code, string $path = self::XML_PATH, int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param string $code
     * @param string $path
     * @param integer|null $storeId
     * @return false|string[]
     */
    public function getStoreConfigArray(string $code, string $path = self::XML_PATH, int $storeId = null)
    {
        return explode(',', $this->scopeConfig->getValue(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        ));
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->moduleList
                   ->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function isModuleActive(): bool
    {
        return $this->getStoreConfigFlag('enabled');
    }

    public function getMaximumQuantity(): ?int
    {
        return $this->getStoreConfig('maximum_qty') ? (int)$this->getStoreConfig('maximum_qty') : null;
    }

    public function getProductListViewJqueryContainerSelector(): string
    {
        return $this->getStoreConfig('jquery_container_selector', self::XML_PATH_PRODUCT_LIST_VIEW);
    }

    public function getProductViewJqueryContainerSelector(): string
    {
        return $this->getStoreConfig('jquery_container_selector', self::XML_PATH_PRODUCT_VIEW);
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
