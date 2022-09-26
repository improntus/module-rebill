# Magento 2 Improntus Rebill Module

    improntus/module-rebill

## Main Functionalities
Rebill payment method module by Improntus
- Allow the store owner to add subscription products to his catalog
- Allow the store owner to sell subscription and one time purchase products in the same order
- Allow the store owner to choose between different gateways (MercadoPago, Stripe, DLocal) without having to actually integrate to those gateways directly from Magento
- Allow the customer to buy subscription and one time purchase products through the selected gateway
- Allow both, customer and store owner, to manage the subscriptions ordered on the platform

## Installation 

### Type 1: Zip file

 - Unzip the zip file in `app/code/Improntus/Rebill`
 - Enable the module by running `php bin/magento module:enable Improntus_Rebill`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.improntus.com`
 - Add the composer repository to the configuration by running `composer config repositories.improntus composer https://repo.improntus.com/`
 - Install the module composer by running `composer require improntus/module-rebill`
 - enable the module by running `php bin/magento module:enable Improntus_Rebill`
 - apply database updates by running `php bin/magento setup:upgrade`

