/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'mage/translate'
    ],
    function (
        Component,
        priceUtils,
        quote,
        $,
        $t,
        validations
    ) {
        'use strict';
        return Component.extend({
            defaults:{
                template: 'Improntus_Rebill/payment/rebill'
            },
            initialize: function () {
                this._super();
                this.cardField = null;
                this.rebillData = window.checkoutConfig.payment.improntus_rebill;
            },
            redirectAfterPlaceOrder: false,
            initObservable: function ()
            {
                this._super()
                    .observe('paymentReady');

                return this;
            },
            isPaymentReady: function () {
                return true; /*this.paymentReady();*/
            },
            /**
             * Get action url for payment method.
             * @returns {String}
             */
            getActionUrl: function () {
                if (window.checkoutConfig.payment['improntus_rebill'] != undefined) {
                    return window.checkoutConfig.payment['improntus_rebill']['actionUrl'];
                }
                return '';
            },
            /**
             * Places order in pending payment status.
             */
            afterPlaceOrder: function () {
                window.location = this.getActionUrl();
            },
            /**
             * Places order in pending payment status.
             */
            placeSmartFieldsPayment: function ()
            {
                // var cardError = $('.rebill-card-error');
                // var cardHolderName = $('#card-holder-name');
                // var cardDocument = $('#card-document');
                //
                // cardError.empty().hide();
                //
                // if(cardDocument.val() === '')
                // {
                //     cardError.append('<p>'+$t('Document is required.')+'</p>');
                // }
                //
                // if(cardHolderName.val() === '')
                // {
                //     cardError.append('<p>'+$t('Card Holder Name is required.')+'</p>');
                // }
                //
                // var errors = cardError.html().length;
                //
                // if(errors === 0)
                // {
                //     this.submitSmartFieldsForm();
                // }
                // else {
                //     $('.rebill-card-error').show();
                // }
            },
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },
            // buildInstallments: function (installmentsInput, installmentsPlan) {
            //
            //     const installmentsOptions = installmentsPlan.installments.reduce(function (options, plan)
            //     {
            //         var totalAmount = priceUtils.formatPrice(plan.total_amount);
            //         var installmentAmount = priceUtils.formatPrice(plan.installment_amount);
            //
            //         options += "<option value=" + plan.id + ">" + plan.installments + " of " + installmentAmount
            //             + " (Total : " + totalAmount + ")</option>";
            //         return options;
            //     }, "");
            //     installmentsInput.disabled = false;
            //     installmentsInput.innerHTML = installmentsOptions;
            // },
            // initCreditCardForm: function () {
            //     var smartFields = document.querySelector(".rebill-section");
            //     var form = smartFields.querySelector('form');
            //
            //     const pansmartFields = fields.create('pan', {
            //         style: {
            //             base: {
            //                 fontSize: "16px",
            //                 fontFamily: "Quicksand, Open Sans, Segoe UI, sans-serif",
            //                 lineHeight: '18px',
            //                 fontSmoothing: 'antialiased',
            //                 fontWeight: '500',
            //                 color: "#666",
            //                 '::placeholder': {
            //                     color: "#c1c1c1"
            //                 },
            //                 iconColor: "#c1c1c1"
            //             },
            //             autofilled: {
            //                 color: "#000000"
            //             }
            //         },
            //         placeholder: "XXXX XXXX XXXX XXXX"
            //     });
            //
            //     this.cardField = pansmartFields;
            //
            //     var actualBrandsmartFields = null;
            //     var self = this;
            //     var quoteData = window.checkoutConfig.quoteData;
            //
            //     pansmartFields.on('brand', function (event) {
            //
            //         if (event.brand && actualBrandsmartFields !== event.brand) {
            //             actualBrandsmartFields = event.brand;
            //
            //             rebillInstance.createInstallmentsPlan(pansmartFields, parseFloat(quoteData.grand_total), self.rebillData.currency) /*self.rebillData.currency*/
            //                 .then((result) => {
            //                 var installmentsSelect = form.querySelector('.installments');
            //
            //             self.buildInstallments(installmentsSelect, result.installments);
            //         }).catch((result) => {
            //             console.error(result);
            //
            //             var errorField = $('.rebill-card-error');
            //             var errorMessage = result.error.message;
            //
            //             if(errorMessage == 'There was an error getting the installments.')
            //             {
            //                 errorMessage = $t('There was an error getting the installments.');
            //             }
            //
            //             errorField.show().append('<p>' + errorMessage + '</p>');
            //         });
            //         }
            //     });
            //
            //     const expirationsmartFields = fields.create('expiration', {
            //         style: {
            //             base: {
            //                 fontSize: "16px",
            //                 fontFamily: "Quicksand, Open Sans, Segoe UI, sans-serif",
            //                 lineHeight: '18px',
            //                 fontSmoothing: 'antialiased',
            //                 fontWeight: '500',
            //                 color: "#666",
            //                 '::placeholder': {
            //                     color: "#c1c1c1"
            //                 }
            //             },
            //             autofilled: {
            //                 color: "#000000"
            //             }
            //         },
            //         placeholder:  "mm/yy"
            //     });
            //
            //     const cvvsmartFields = fields.create('cvv', {
            //         style: {
            //             base: {
            //                 fontSize: "16px",
            //                 fontFamily: "Quicksand, Open Sans, Segoe UI, sans-serif",
            //                 lineHeight: '18px',
            //                 fontSmoothing: 'antialiased',
            //                 fontWeight: '500',
            //                 color: "#666",
            //                 '::placeholder': {
            //                     color: "#c1c1c1"
            //                 }
            //             }
            //         },
            //         placeholder: "XXX"
            //     });
            //
            //     pansmartFields.mount(document.getElementById('card-holder-pan'));
            //     expirationsmartFields.mount(document.getElementById('card-holder-expiration'));
            //     cvvsmartFields.mount(document.getElementById('card-holder-cvv'));
            // },
            // submitSmartFieldsForm: function () {
            //     var cardHolderName = $('#card-holder-name').val();
            //
            //     var cardField = this.cardField;
            //     var self = this;
            //
            //     $('.payment-method-billing-address.rebill .checkout-billing-address fieldset .actions-toolbar.eset .primary button.action-update').trigger('click');
            //
            //     rebillInstance.createToken(cardField, {
            //         name: cardHolderName
            //     }).then(function(result) {
            //         self.rebillTokenHandler(result.token);
            //     }).catch((result) => {
            //         if (result.error)
            //         {
            //             var errorField = $('.rebill-card-error');
            //             var errorMessage = result.error.message;
            //
            //             if(errorMessage == 'There was an error getting the installments.')
            //             {
            //                 errorMessage = $t('There was an error getting the installments.');
            //             }
            //
            //             errorField.show().append('<p>' + errorMessage + '</p>');
            //         }
            //     });
            // },
            // rebillTokenHandler: function (token) {
            //     var form = document.getElementById('rebill-payment-form');
            //     var cardDocument = $('#card-document').val();
            //     var tokenInput = document.createElement('input');
            //     var installmentsSelected = $('#card-holder-installments').val();
            //     var self = this;
            //
            //     tokenInput.setAttribute('type', 'hidden');
            //     tokenInput.setAttribute('name', 'rebillToken');
            //     tokenInput.setAttribute('value', token);
            //     form.appendChild(tokenInput);
            //
            //     $.ajax('/rebill/payment/token',
            //     {
            //         method  : 'GET',
            //         data    : '&payment_token=' + token + '&document=' + cardDocument + '&installments=' + installmentsSelected,
            //         dataType: 'json',
            //         global  : true,
            //         contentType: 'application/json',
            //         success : function(response)
            //         {
            //            self.placeOrder();
            //         },
            //         error   : function(e, status)
            //         {
            //             console.log(e);
            //             console.log(status);
            //         }
            //     });
            // },
            // getDocumentNumber: function()
            // {
            //     var customerData = window.checkoutConfig.customerData;
            //     return customerData.taxvat !== undefined ? customerData.taxvat.replace(/[^a-zA-Z0-9]/g, '') : '';
            // }
        });
    }
);
