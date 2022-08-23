/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */
define([
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Improntus_Rebill/checkout/summary/initial_cost'
            },
            totals: quote.getTotals(),
            isDisplayed: function () {
                return this.isFullMode() && totals.getSegment('rebill_initial_cost').value;
            },

            getRawValue: function () {
                var price = 0;
                if (this.totals() && totals.getSegment('rebill_initial_cost')) {
                    price = totals.getSegment('rebill_initial_cost').value;
                }
                return price;
            },

            getValue: function () {
                var price = this.getRawValue();
                return this.getFormattedPrice(price);
            },
        });
    }
);
