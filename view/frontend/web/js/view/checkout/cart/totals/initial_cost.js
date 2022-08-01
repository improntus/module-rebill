define(['Improntus_Rebill/js/view/checkout/summary/initial_cost'],
    function (Component) {
        'use strict';

        return Component.extend({

            /**
             * @override
             */
            isDisplayed: function () {
                return this.getRawValue() !== 0;
            }
        });
    }
);
