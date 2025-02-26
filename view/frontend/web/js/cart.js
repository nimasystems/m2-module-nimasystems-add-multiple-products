define(
    [
        'jquery',
        'ko',
        'underscore',
        'Magento_Customer/js/customer-data',
        'Magento_Catalog/js/price-utils',
        'domReady'
    ],
    function (
        $,
        ko,
        _,
        customerData,
        priceUtils,
        domReady,
    ) {
        'use strict';

        let instance;

        function Cart() {
            if (instance) {
                return instance;
            }
            instance = this;

            this.cartData = {};
        }

        Cart.prototype = {

            getCartData: function () {
                return this.cartData;
            },

            getProductData: function (productId) {
                return this.cartData[productId];
            },

            _updateCartData: function (data) {
                const qty = data['qty'] || 0;
                const price = data['product_price_value'] || 0;
                const priceRowTotal = data['product_row_total'] || '';

                this.cartData[data.product_id] = {
                    qty: qty,
                    price: price,
                    priceRowTotal: priceRowTotal
                };
            },

            initialize: function () {
                const cart = customerData.get('cart');

                if (!cart) {
                    console.error('This module requires the cart data to be available / aka minicart');
                    return;
                }

                function updateCartItems(productItems) {
                    const itemsToRemove = [];

                    // reset non-existing products
                    _.each(instance.cartData,
                        function (item, productId) {
                            if (!_.find(productItems, {product_id: productId})) {
                                itemsToRemove.push(parseInt(productId));
                            }
                        }
                    );

                    instance.cartData = {};

                    // update existing products
                    _.each(productItems,
                        function (item) {
                            instance._updateCartData(item);
                        }
                    )

                    // console.log('CartData updated', {
                    //     cartData: instance.cartData,
                    //     itemsToRemove: itemsToRemove
                    // });

                    return itemsToRemove;
                }

                function informListeners(nonexistingProductIds) {

                    // console.log('informListeners', {
                    //     nonexistingProductIds
                    // });

                    $('.qty-controller').each(function () {
                        const mageAmpQtyController = $(this).data('mageAmpQtyController');

                        if (mageAmpQtyController) {
                            const productId = mageAmpQtyController.options.productId;

                            if (nonexistingProductIds.includes(productId)) {
                                mageAmpQtyController.updateProductData(null);
                            } else {
                                const productData = instance.getProductData(productId);

                                if (productData) {
                                    mageAmpQtyController.updateProductData(productData);
                                }
                            }
                        }
                    });
                }

                cart.subscribe(function (updatedCart) {
                    const nonexistingProductIds = updateCartItems(updatedCart.items);
                    informListeners(nonexistingProductIds);
                }, this);

                // first time init
                domReady(function () {
                    updateCartItems(cart().items);
                });
            },
        };

        const cart = new Cart();
        cart.initialize();

        return cart;
    }
);
