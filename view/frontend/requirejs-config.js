/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Improntus_Rebill/js/model/checkout-data-resolver': true
            }
        }
    },
    map: {
        '*':{
            rebill: 'https://sdk.rebill.to/v2/rebill.min.js',
        }
    }
};
