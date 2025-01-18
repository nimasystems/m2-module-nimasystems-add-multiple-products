define(
    [
        'jquery',
        'ko',
        'ampCart',
        'domReady',
        'mage/translate'
    ],
    function (
        $,
        ko,
        ampCart,
        domReady,
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
            qtyInputElHelp: null,
            qtyIncEl: null,
            qtyDecEl: null,
            statusEl: null,

            qty: 0,
            forcedUpdatesNext: 0,
            totalAmount: 0,
            productPrice: 0,
            productPriceRowTotal: '',
            productDataInitialized: false,
            isEditingQty: false,
            isProductList: false,
            isProductView: false,

            options: {
                addUrl: null,
                productId: null,
                valueFormatString: null,
                stockConfig: {
                    usesStock: false,
                    inStock: false,
                    minSaleQty: null,
                    maxSaleQty: null,
                    qtyUsesDecimals: false,
                    qtyUsesIncrements: false,
                    qtyIncrements: 1,
                },
                plSelector: '.product.actions',
                pvSelector: '.product-add-form',
            },

            updateProductData(data, forceDec) {

                this.productPrice = data.price;
                this.productPriceRowTotal = data.priceRowTotal;

                this._updateProductQty(data.qty);

                if ((forceDec === undefined || true) && this.forcedUpdatesNext > 0) {
                    this.forcedUpdatesNext--;
                }
            },

            _create: function () {
                const me = this;

                domReady(function () {
                    me._initializeController();
                });
            },

            _initTemplate: function () {
                const template = '<button class="qty-dec" type="button" title="' + $t('Decrease Quantity') + '"></button>' +
                    '<div class="quantity-value">' +
                    '<span class="value">' +
                    '<span title="' + $t('Click to edit') + '" class="qty-view">' +
                    '<span aria-label="' + $t('Current Quantity') + '" class="item-qty"></span>' +
                    '</span>' +
                    '<input style="display: none;" type="text" class="qty-input" value="1" /><span class="input-help"></span>' +
                    '</span>' +
                    '</div>' +
                    '<button class="qty-inc" type="button" title="' + $t('Increase Quantity') + '"></button>';
                this.containerEl.html(template);
            },

            _appendStatusContainer: function (container) {
                if (this.isProductView) {
                    this._getStatusContainer().prepend(this.statusEl);
                } else if (this.isProductList) {
                    this._getStatusContainer().append(this.statusEl);
                }
            },

            _getStatusContainer: function () {
                if (this.isProductView) {
                    // TODO: this should be a setting
                    return this.containerEl.closest('.price-with-form');
                } else if (this.isProductList) {
                    return this.containerEl.closest('.item.product').find('.image-product');
                }
            },

            _updateProductQty(qty) {
                if ((!this.forcedUpdatesNext && qty === this.qty) || !this.productDataInitialized || !this.productPrice) {
                    return;
                }

                this._log('updateProductQty', qty);

                this.qty = qty;
                this.totalAmount = this.qty * this.productPrice;

                const qtyPickerVisible = this.qty > 0;

                this.qtyEl.html(this._formatValue(this.qty));
                this.qtyInputEl.val(this.qty);
                this.submitBtnEl.toggle(!qtyPickerVisible);
                this.containerEl.toggle(qtyPickerVisible);
                this._updateIncDecButtons();

                if (qtyPickerVisible) {
                    if (!this.statusEl) {
                        this.statusEl = $('<div/>').addClass('amp-product-overlay');
                        this._appendStatusContainer();
                    }
                } else {
                    this.statusEl.remove();
                }

                const msg = this.qty === 1 ? $t('%1 item in cart for %2') : $t('%1 items in cart for %2');
                this.statusEl.html(msg
                    .replace('%1', this.qty)
                    .replace('%2', this.productPriceRowTotal)
                );

                if (this.originalQtyEl) {
                    this.originalQtyEl.toggleClass('qty-hidden', qtyPickerVisible);
                }
            },

            _log: function (str, value, type) {
                const logM = type ?? 'log';
                const m = '[ampQtyController_ ' + this.options.productId + ']: ' + str;
                if (value !== undefined) {
                    console[logM](m, value);
                } else {
                    console[logM](m);
                }
            },

            _initializeController: function () {
                if (this.productDataInitialized) {
                    return;
                }

                this._log('initializeController', this.options);

                this.containerEl = $(this.element);

                this._initTemplate();

                this.qtyEl = this.containerEl.find('.item-qty')
                this.qtyInputEl = this.containerEl.find('.qty-input');
                this.qtyInputElHelp = this.containerEl.find('.input-help');
                this.qtyViewEl = this.containerEl.find('.qty-view');
                this.qtyIncEl = this.containerEl.find('.qty-inc');
                this.qtyDecEl = this.containerEl.find('.qty-dec');

                const me = this;

                this.qtyIncEl.on('click', () => {
                    me._increaseQty();
                });

                this.qtyDecEl.on('mousedown', () => {
                    me._decreaseQty();
                });

                this.qtyViewEl.on('click', () => {
                    me._toggleEditQty(true);
                });

                this._initEditInput();
                this._initLayout();

                this.productDataInitialized = true;
                this.element.data('amp-qtycontroller-initialized', 1);

                const productData = ampCart.getProductData(this.options.productId);

                if (productData) {
                    this._updateFirstTimeProductData(productData);
                }
            },

            _updateFirstTimeProductData: function (productData) {
                this.updateProductData(productData, false);
            },

            _initLayout: function () {
                const addForm = this.containerEl.closest(this.options.pvSelector);
                const actionsEl = this.containerEl.closest(this.options.plSelector);

                if (actionsEl.length) {
                    this._productListingInit(actionsEl);
                } else if (addForm.length) {
                    this._productPageInit(addForm);
                } else {
                    this._log('Could not init layout - parent not found', null, 'error');
                }
            },

            _sendRequest: function (addUrl, data, completion) {
                this._setProgress(true);

                const me = this;

                $.ajax(
                    {
                        type: 'post',
                        url: addUrl,
                        data: data,
                        dataType: 'json',
                        success: function (data) {
                            me._setProgress(false);

                            const success = data['success'] || false;

                            if (completion) {
                                completion(success);
                            }
                        },
                        error: function (xhr, status, error) {
                            me._setProgress(false);

                            if (completion) {
                                completion(false);
                            }
                        }
                    }
                );
            },

            _isValidInput: function (v) {
                const incrValue = this._incrementValue();
                console.log('is valid', v);

                return (!isNaN(v) &&
                    (!this.options.stockConfig.minSaleQty ||
                        (this.options.stockConfig.minSaleQty && v >= this.options.stockConfig.minSaleQty)) &&
                    (!this.options.stockConfig.maxSaleQty ||
                        (this.options.stockConfig.maxSaleQty && v <= this.options.stockConfig.maxSaleQty)) &&
                    (incrValue === 1 || (v % incrValue) === 0)
                );
            },

            _updateQtyFromField: function () {
                const v = parseFloat(this.qtyInputEl.val());

                if (this._isValidInput(v)) {
                    this._updateQty(v);
                } else {
                    this._log('invalid value', v);
                }
            },

            _presentInvalidData: function (value) {
                const msgParts = [];

                if (isNaN(value)) {
                    msgParts.push($t('Please enter a valid number'));
                }

                if (this.options.stockConfig.minSaleQty && value < this.options.stockConfig.minSaleQty) {
                    msgParts.push($t('Minimum quantity is %1').replace('%1', this.options.stockConfig.minSaleQty));
                }

                if (this.options.stockConfig.maxSaleQty && value > this.options.stockConfig.maxSaleQty) {
                    msgParts.push($t('Maximum quantity is %1').replace('%1', this.options.stockConfig.maxSaleQty));
                }

                if (this.options.stockConfig.qtyUsesIncrements && value % this.options.stockConfig.qtyIncrements !== 0) {
                    msgParts.push($t('Quantity must be a multiple of %1').replace('%1', this.options.stockConfig.qtyIncrements));
                }

                alert($t("Value is not valid:") + "\n\n-" + msgParts.join("\n-"));
            },

            _initEditInput: function () {

                const me = this;

                let blurEnabled = true;

                this.qtyInputEl.on('blur', () => {
                    if (blurEnabled) {
                        me._toggleEditQty(false);
                        me._updateQtyFromField();
                    }
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
                    blurEnabled = false;

                    const isEnterPressed = e.key === 'Enter' || e.keyCode === 13 || e.which === 13;
                    const isEscPressed = e.key === 'Escape' || e.keyCode === 27 || e.which === 27;
                    const isBackspacePressed = e.key === 'Backspace' || e.keyCode === 8 || e.which === 8;

                    const currentVal = me.qtyInputEl.val();
                    const unsetVal = (isBackspacePressed && currentVal.length > 0 ? currentVal.substring(0, currentVal.length - 1) : currentVal) +
                        (isEnterPressed || isEscPressed || isBackspacePressed ? '' : e.key);
                    const isValid = me._validateInput(unsetVal);

                    if (isEnterPressed) {
                        if (isValid) {
                            me._toggleEditQty(false);
                            me._updateQtyFromField();
                        } else {
                            me._presentInvalidData(unsetVal);
                        }
                    } else if (isEscPressed) {
                        me._toggleEditQty(false);
                    }

                    blurEnabled = true;
                });

                // Handle paste event
                this.qtyInputEl.on('paste', function (e) {
                    let pastedData = (e.originalEvent || e).clipboardData.getData('text');
                    if (!/^\d*\.?\d*$/.test(pastedData)) {
                        e.preventDefault();
                    }
                });
            },

            _validateInput: function (value) {
                const isValid = this._isValidInput(value);
                this.qtyInputEl.toggleClass('invalid-data', !isValid);
                return isValid;
            },

            _toggleEditQty: function (value) {
                this.isEditingQty = value !== undefined ? value : !this.isEditingQty;

                if (this.isEditingQty) {
                    this.qtyInputEl.removeClass('invalid-data');
                    this.qtyInputEl.val(this.qty);
                }

                this.qtyViewEl.toggle(!this.isEditingQty);
                this.qtyInputEl.toggle(this.isEditingQty);

                this.qtyViewEl.parent().toggleClass('edit-mode', this.isEditingQty);
                // this.qtyInputElHelp.toggle(this.isEditingQty);

                if (this.isEditingQty) {
                    // this.qtyInputElHelp.html('min: 1, max: 3, increment: 1.5');

                    const me = this;

                    setTimeout(function () {
                        me.qtyInputEl.focus();
                        me.qtyInputEl.select();
                    }, 10);
                }
            },

            _setProgress: function (value) {
                this.qtyIncEl.prop('disabled', value || !this._canIncrease());
                this.qtyDecEl.prop('disabled', value || !this._canDecrease());
                this.qtyInputEl.prop('disabled', value);
                this.statusEl.toggleClass('in-progress', value);
            },

            _canIncrease: function () {
                return !this.options.stockConfig.maxSaleQty || this.qty < this.options.stockConfig.maxSaleQty;
            },

            _canDecrease: function () {
                return !this.options.stockConfig.minSaleQty || this.qty >= this.options.stockConfig.minSaleQty;
            },

            _sendReq: function (qty, qtySend, exact) {

                const currentQty = this.qty;

                this.forcedUpdatesNext++;

                this.qty = qty;
                this.qtyEl.html(this._formatValue(this.qty));
                this._updateProductQty(this.qty);

                var me = this;

                this._sendRequest(this.options.addUrl, {
                    product: this.options.productId,
                    qty: qtySend,
                    exact: exact
                }, function (success) {
                    if (!success) {
                        // revert back
                        me._updateProductQty(currentQty);
                    }
                });
            },

            _updateQty: function (value) {
                if (value === this.qty) {
                    return;
                }

                this._log(`updateQty ${value}`);
                this._sendReq(value, value, 1);
            },

            _incrementValue: function () {
                return this.options.stockConfig.qtyUsesIncrements && this.options.stockConfig.qtyIncrements ?
                    this.options.stockConfig.qtyIncrements : 1;
            },

            _increaseQty: function () {
                if (!this._canIncrease()) {
                    return;
                }

                this._log('increaseQty');

                const v = this._incrementValue();
                this._sendReq(this.qty + v, v);
            },

            _decreaseQty: function () {
                if (!this._canDecrease()) {
                    return;
                }

                this._log('decreaseQty');

                const v = this._incrementValue();
                this._sendReq(this.qty - v, -v);
            },

            _formatValue: function (value) {
                if (this.options.valueFormatString) {
                    return this.options.valueFormatString.replace('{value}', value);
                }

                return value;
            },

            _updateIncDecButtons: function () {
                this.qtyDecEl.prop('disabled', !this._canDecrease());
                this.qtyIncEl.prop('disabled', !this._canIncrease());
            },

            _productPageInit: function (parent) {
                this.isProductView = true;

                const fields = parent.find('.fields');
                this.submitBtnEl = parent.find('.action.tocart.primary');
                this.originalQtyEl = fields.find('.field.qty');

                if (fields.length) {
                    this.containerEl.prependTo(fields);
                }
            },

            _productListingInit: function (parent) {
                this.isProductList = true;
                this.submitBtnEl = parent.find('.action.tocart.primary');

                if (parent.length) {
                    this.containerEl.prependTo(parent);
                }
            },
        });

        return $.mage.ampQtyController;
    }
);
