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
            qtyIncEl: null,
            qtyDecEl: null,

            qty: 0,
            isEditingQty: false,

            options: {
                addUrl: null,
                productId: null,
                maxQty: null,
                initialQty: 0,
                valueFormatString: null,
            },

            log: function (str, value, type) {
                const logM = type ?? 'log';
                const m = '[ampQtyController] ' + this.options.productId + ': ' + str;
                if (value !== undefined) {
                    console[logM](m, value);
                } else {
                    console[logM](m);
                }
            },

            _create: function () {
                if (this.element.data('amp-qtycontroller-initialized')) {
                    return;
                }

                this.log('init', this.options);

                this.containerEl = $(this.element);
                this.qtyEl = this.containerEl.find('.item-qty')
                this.qtyInputEl = this.containerEl.find('.qty-input');
                this.qtyViewEl = this.containerEl.find('.qty-view');
                this.qtyIncEl = this.containerEl.find('.qty-inc');
                this.qtyDecEl = this.containerEl.find('.qty-dec');

                const me = this;

                this.qtyIncEl.on('click', () => {
                    me.increaseQty();
                });

                this.qtyDecEl.on('mousedown', () => {
                    me.decreaseQty();
                });

                this.qtyViewEl.on('click', () => {
                    me.toggleEditQty(true);
                });

                this.initEditInput();
                this.initLayout();
                this.initData();

                this.element.data('amp-qtycontroller-initialized', 1);
            },

            initLayout: function () {
                const addForm = this.containerEl.closest('.product-add-form');
                const actionsEl = this.containerEl.closest('.product.actions');

                if (actionsEl.length) {
                    this.productListingInit(actionsEl);
                } else if (addForm.length) {
                    this.productPageInit(addForm);
                } else {
                    this.log('Could not init layout - parent not found', null, 'error');
                }
            },

            sendRequest: function (addUrl, data, completion) {
                this.setBlocking(true);

                const me = this;

                $.ajax(
                    {
                        type: 'post',
                        url: addUrl,
                        data: data,
                        dataType: 'json',
                        success: function (data) {
                            me.setBlocking(false);

                            const success = data['success'] || false;

                            if (completion) {
                                completion(success);
                            }
                        },
                        error: function (xhr, status, error) {
                            me.setBlocking(false);

                            if (completion) {
                                completion(false);
                            }
                        }
                    }
                );
            },

            initEditInput: function () {

                const me = this;

                this.qtyInputEl.on('blur', () => {
                    me.toggleEditQty(false);
                    me.updateQty(me.qtyInputEl.val());
                });

                this.qtyInputEl.on('input', function (e) {
                    // Allow only numbers and one decimal point
                    let value = $(this).val();
                    if (!/^\d*\.?\d*$/.test(value)) {
                        $(this).val(value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'));
                    }
                });

                // Prevent invalid characters on keypress
                this.qtyInputEl.on('keypress', function (e) {
                    const char = String.fromCharCode(e.which);
                    if (!/[0-9.]/.test(char)) {
                        e.preventDefault();
                    }

                    // Ensure only one decimal point
                    if (char === '.' && $(this).val().includes('.')) {
                        e.preventDefault();
                    }
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

                // Handle paste event
                this.qtyInputEl.on('paste', function (e) {
                    let pastedData = (e.originalEvent || e).clipboardData.getData('text');
                    if (!/^\d*\.?\d*$/.test(pastedData)) {
                        e.preventDefault();
                    }
                });
            },

            toggleEditQty: function (value) {
                this.isEditingQty = value !== undefined ? value : !this.isEditingQty;
                this.qtyViewEl.toggle(!this.isEditingQty);
                this.qtyInputEl.toggle(this.isEditingQty);
                this.qtyViewEl.parent().toggleClass('edit-mode', this.isEditingQty);

                if (this.isEditingQty) {
                    const me = this;

                    setTimeout(function () {
                        me.qtyInputEl.focus();
                        me.qtyInputEl.select();
                    }, 10);
                }
            },

            updateQty: function (value) {

                const v = parseFloat(value);

                if (!v || isNaN(v) || v === this.qty ||
                    (this.options.maxQty && v > this.options.maxQty)) {
                    return;
                }

                this.log(`updateQty ${v}`);

                const currentQty = this.qty;
                this.qty = v;
                this.qtyEl.html(this.formatValue(this.qty));
                this.updateIncDecButtons();

                const me = this;

                this.sendRequest(this.options.addUrl, {
                    product: this.options.productId,
                    qty: v,
                    exact: 1,
                }, function (success) {
                    if (!success) {
                        // revert back
                        me.updateProductQty(currentQty);
                    }
                });
            },

            setBlocking: function (value) {
                this.qtyIncEl.prop('disabled', value);
                this.qtyDecEl.prop('disabled', value);
                this.qtyInputEl.prop('disabled', value);
            },

            canIncrease: function () {
                return !this.options.maxQty || this.qty < this.options.maxQty;
            },

            canDecrease: function () {
                return this.qty > 0
            },

            increaseQty: function () {
                if (!this.canIncrease()) {
                    return;
                }

                this.log('increaseQty');

                this.sendRequest(this.options.addUrl, {
                    product: this.options.productId,
                    qty: 1,
                });
            },

            decreaseQty: function () {
                if (!this.canDecrease()) {
                    return;
                }

                this.log('decreaseQty');

                this.sendRequest(this.options.addUrl, {
                    product: this.options.productId,
                    qty: -1,
                });
            },

            initData: function () {
                this.updateProductQty(this.options.initialQty);
            },

            formatValue: function (value) {
                if (this.options.valueFormatString) {
                    return this.options.valueFormatString.replace('{value}', value);
                }

                return value;
            },

            updateIncDecButtons: function () {
                this.qtyDecEl.prop('disabled', !this.canDecrease());
                this.qtyIncEl.prop('disabled', !this.canIncrease());
            },

            updateProductQty(value) {
                if (value === this.qty) {
                    return;
                }

                this.qty = value;

                const qtyPickerVisible = this.qty > 0;

                this.qtyEl.html(this.formatValue(this.qty));
                this.qtyInputEl.val(this.qty);
                this.submitBtnEl.toggle(!qtyPickerVisible);
                this.containerEl.toggle(qtyPickerVisible);
                this.updateIncDecButtons();

                if (this.originalQtyEl) {
                    this.originalQtyEl.toggleClass('hidden', qtyPickerVisible);
                }
            },

            productPageInit: function (parent) {
                const fields = parent.find('.fields');
                this.submitBtnEl = parent.find('.action.tocart.primary');
                this.originalQtyEl = fields.find('.field.qty');

                if (fields.length) {
                    this.containerEl.prependTo(fields);
                }
            },

            productListingInit: function (parent) {
                this.submitBtnEl = parent.find('.action.tocart.primary');

                if (parent.length) {
                    this.containerEl.prependTo(parent);
                }
            },
        });

        return $.mage.ampQtyController;
    }
);
