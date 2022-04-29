
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'improntus_rebill',
                component: 'Improntus_Rebill/js/view/payment/method-renderer/rebill-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({
        });
    }
);
