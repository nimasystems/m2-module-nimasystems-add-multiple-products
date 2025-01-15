define(
    [
        'jquery',
        'ko',
        'underscore',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $,
        ko,
        _,
        customerData
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

            getProductQty: function (productId) {
                return this.cartData[productId] || 0;
            },

            initialize: function () {
                const cart = customerData.get('cart');

                if (!cart) {
                    console.error('This module requires the cart data to be available / aka minicart');
                    return;
                }

                function updateCartItems(productItems) {
                    instance.cartData = {};

                    _.each(productItems,
                        function (item) {
                            instance.cartData[item['product_id']] = item['qty'] || 0;
                        }
                    )

                    console.log('CartData updated', instance.cartData);

                    // TODO: optimize this update - only update products which are updated in the cart
                    $('.qty-controller').each(function () {
                        const mageAmpQtyController = $(this).data('mageAmpQtyController');

                        if (mageAmpQtyController) {
                            const productId = mageAmpQtyController.options.productId;
                            mageAmpQtyController.updateProductQty(instance.getProductQty(productId));
                        }
                    });
                }

                cart.subscribe(function (updatedCart) {
                    updateCartItems(updatedCart.items);
                }, this);
            },
        };

        const cart = new Cart();
        cart.initialize();

        return cart;
    }
);
