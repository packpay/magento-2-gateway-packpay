# About The Project


This plugin enables packpay payment gateway for magento 2. Please do not hesitate to contact [Packpay](https://packpay.ir/contact)
if you have any questions.

This plugin is developed by packpay development team. More info at: [Packpay](https://lab.packpay.ir/)

Tested on magento 2.2

# Instalation

In order to install this plugin, download and copy it under "app/code/Packpay/Gateway"(create Packpay and Gateway foulders).
After that run these commands in your magento root direcory:
1. ```php bin/magento setup:upgrade```
2. ```php bin/magento setup:di:compile```
3. ``` php bin/magento setup:static-content:deploy -f ```
4. ```php bin/magento setup:di:compile```

If success, you can see packpay setting at Store > Configuration > Sales > Payment methods

