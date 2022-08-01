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
            jquery_card: 'Improntus_Rebill/js/jquery.card',
            rebill: 'https://sdk.rebill.to/v2/rebill.min.js',
        }
    }
};
