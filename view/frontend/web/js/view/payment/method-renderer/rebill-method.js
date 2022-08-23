/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery'
    ],
    function (
        Component,
        quote,
        additionalValidators,
        placeOrderAction,
        fullScreenLoader,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Improntus_Rebill/payment/rebill'
            },
            afterPlaceOrder: function () {
                window.location.href = window.checkoutConfig.payment['improntus_rebill']['actionUrl'];
            },
            placeOrder: function (data, event) {
                let self = this;
                if (event) {
                    event.preventDefault();
                                }
                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    this.getPlaceOrderDeferredObject()
                        .fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        })
                        .done(function () {
                            self.afterPlaceOrder();
                        })
                        .always(function () {
                            self.isPlaceOrderActionAllowed(true);
                            fullScreenLoader.stopLoader();
                        });
                return true;
                }
                return false;
            },
            getPlaceOrderDeferredObject: function () {
                $('button.checkout').attr('disabled', 'disabled');
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },
        });
    }
);
