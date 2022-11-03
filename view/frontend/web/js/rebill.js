define([
    'ko',
    'uiComponent',
    'jquery',
    'mage/translate',
    'rebill_sdk'
], function (ko, Component, $, $t) {
    'use strict';
    return Component.extend({
        initOptions: {
            organization_id: '',
            api_url: ''
        },
        rebillOptions: {
            cardHolder: {},
            customer: {},
            transaction: {}
        },
        documentTypes: [],
        urlConfirmation: '',
        rebillCheckout: null,
        initialize: function () {
            this._super();
            this.initRebillElements();
        },
        needCardHolderIdentification: function () {
            return this.documentTypes.length > 0;
        },
        initRebillElements: function () {
            this.rebillCheckout = new Rebill.PhantomSDK(this.initOptions);
            this.rebillCheckout.setCustomer(this.rebillOptions.customer);
            this.rebillCheckout.setCardHolder(this.rebillOptions.cardHolder);
            this.rebillCheckout.setTransaction(this.rebillOptions.transaction);
            this.rebillCheckout.setText({
                card_number: $t('Card Number'),
                pay_button: $t('Pay'),
                error_messages: {
                    emptyCardNumber: $t('Enter a card number'),
                    invalidCardNumber: $t('Card number is invalid'),
                    emptyExpiryDate: $t('Enter an expiry date'),
                    monthOutOfRange: $t('Expiry month must be between 01 and 12'),
                    yearOutOfRange: $t('Expiry year cannot be in the past'),
                    dateOutOfRange: $t('Expiry date cannot be in the past'),
                    invalidExpiryDate: $t('Expiry date is invalid'),
                    emptyCVC: $t('Enter a CVC'),
                    invalidCVC: $t('CVC is invalid'),
                },
            });

            let errorsMessage = $('.rebill-errors');
            let self = this;

            this.rebillCheckout.setCallbacks({

                onSuccess: function (response) {
                    $("body").trigger('processStart');
                    errorsMessage.html('');
                    if (response.invoice) {
                        window.location.href = self.urlConfirmation + `?invoice_id=${response.invoice.id}`;
                    } else {
                        let msg = "";
                        if(response.failedTransaction.paidBags.length >= 1){
                            msg = $t(response.failedTransaction.paidBags['0'].payment.errorMessage.replace(`'`, ''));
                        }
                        errorsMessage.append($('<div class="error-message"></div>')
                            .text(msg));
                        $("body").trigger('processStop');
                    }

                },
                onError: function (error) {
                    errorsMessage.html('')
                        .append($('<div class="error-message"></div>')
                            .text($t('The payment can\'t be processed, try again with another card.')));
                    $("body").trigger('processStop');
                },
            });


            this.rebillCheckout.setElements('rebill_elements');
        },
        selectDocumentType: function (event) {
            this.rebillOptions.cardHolder.identification.type = event.target.value;
            this.rebillCheckout.setCardHolder(this.rebillOptions.cardHolder);
        },
        changeDocumentNumber: function (event) {
            this.rebillOptions.cardHolder.identification.value = event.target.value;
            this.rebillCheckout.setCardHolder(this.rebillOptions.cardHolder);
        },
        changeCardHolderName: function (event) {
            this.rebillOptions.cardHolder.name = event.target.value;
            this.rebillCheckout.setCardHolder(this.rebillOptions.cardHolder);
        }
    });
});
