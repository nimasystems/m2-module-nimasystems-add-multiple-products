define(
    [
        'jquery',
        'ko',
        'ampCart',
        'mage/translate',
    ],
    function (
        $,
        ko,
        ampCart,
        $t
    ) {
        'use strict';

        $.widget('mage.ampQtyController', {

            containerEl: null,
            qtyEl: null,
            originalQtyEl: null,
            submitBtnEl: null,
            qtyViewEl: null,
            qtyInputEl: null,

            isEditingQty: false,

            options: {
                addUrl: null,
                productId: null,
                initialQty: 0,
                isProductListing: false,
            },

            log: function (str, value) {
                const m = 'ampQtyController ' + this.options.productId + ': ' + str;
                if (value !== undefined) {
                    console.log(m, value);
                } else {
                    console.log(m);
                }
            },

            _create: function () {
                if (this.element.data('amp-qtycontroller-initialized')) {
                    return;
                }

                this.containerEl = $(this.element);
                this.qtyEl = this.containerEl.find('.item-qty')
                this.qtyInputEl = this.containerEl.find('.qty-input');
                this.qtyViewEl = this.containerEl.find('.qty-view');

                const me = this;

                this.containerEl.find('.qty-inc').on('click', () => {
                    me.increaseQty();
                });

                this.containerEl.find('.qty-dec').on('mousedown', () => {
                    me.decreaseQty();
                });

                this.qtyViewEl.on('click', () => {
                    me.toggleEditQty(true);
                });

                this.qtyInputEl.on('blur', () => {
                    me.toggleEditQty(false);
                });

                this.qtyInputEl.on('keydown', (e) => {
                    const isEnterPressed = e.key === 'Enter' || e.keyCode === 13 || e.which === 13;
                    const isEscPressed = e.key === 'Escape' || e.keyCode === 27 || e.which === 27;

                    if (isEnterPressed) {
                        me.toggleEditQty(false);
                        me.updateQty(me.qtyInputEl.val());
                    } else if (isEscPressed) {
                        me.toggleEditQty(false);
                    }
                });

                if (this.options.isProductListing) {
                    this.productListingInit();
                } else {
                    this.productPageInit();
                }

                this.initData();

                this.element.data('amp-qtycontroller-initialized', 1);
            },

            _sendAjax: function (addUrl, data) {
                $.ajax(
                    {
                        type: 'post',
                        url: addUrl,
                        data: data,
                        dataType: 'json',
                        success: function (data) {

                        },
                        error: function (xhr, status, error) {
                        }
                    }
                );
            },

            toggleEditQty: function (value) {
                this.isEditingQty = value !== undefined ? value : !this.isEditingQty;
                this.qtyViewEl.toggle(!this.isEditingQty);
                this.qtyInputEl.toggle(this.isEditingQty);

                if (this.isEditingQty) {
                    const me = this;

                    setTimeout(function () {
                        me.qtyInputEl.focus();
                        me.qtyInputEl.select();
                    }, 10);
                }
            },

            updateQty: function (value) {
                this.log(`updateQty ${value}`);

                this._sendAjax(this.options.addUrl, {
                    product: this.options.productId,
                    qty: value,
                    exact: 1,
                });
            },

            increaseQty: function () {
                this.log('increaseQty');

                this._sendAjax(this.options.addUrl, {
                    product: this.options.productId,
                    qty: 1,
                });
            },

            decreaseQty: function () {
                this.log('decreaseQty');

                this._sendAjax(this.options.addUrl, {
                    product: this.options.productId,
                    qty: -1,
                });
            },

            initData: function () {
                this.updateProductQty(this.options.initialQty);
            },

            updateProductQty(value) {
                const qtyPickerVisible = value > 0;

                this.qtyEl.html(`${value}`);
                this.qtyInputEl.val(value);
                this.submitBtnEl.toggle(!qtyPickerVisible);
                this.containerEl.toggle(qtyPickerVisible);

                if (this.originalQtyEl) {
                    this.originalQtyEl.toggleClass('hidden', qtyPickerVisible);
                }
            },

            productPageInit: function () {
                const addForm = this.containerEl.closest('.product-add-form');

                if (!addForm.length) {
                    return;
                }

                const fields = addForm.find('.fields');
                this.submitBtnEl = addForm.find('.action.tocart.primary');
                this.originalQtyEl = fields.find('.field.qty');

                if (fields.length) {
                    this.containerEl.prependTo(fields);
                }
            },

            productListingInit: function () {
                const actionsEl = this.containerEl.closest('.product.actions');
                this.submitBtnEl = actionsEl.find('.action.tocart.primary');

                if (actionsEl.length) {
                    this.containerEl.prependTo(actionsEl);
                }
            },
        });

        return $.mage.ampQtyController;
    }
);
