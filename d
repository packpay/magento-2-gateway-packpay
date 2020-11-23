warning: LF will be replaced by CRLF in Block/Redirect.php.
The file will have its original line endings in your working directory
[1mdiff --git a/Block/Redirect.php b/Block/Redirect.php[m
[1mindex ca04065..77fb250 100644[m
[1m--- a/Block/Redirect.php[m
[1m+++ b/Block/Redirect.php[m
[36m@@ -87,11 +87,10 @@[m [mclass Redirect extends \Magento\Framework\View\Element\Template[m
             $result->status = false;[m
         }[m
 [m
[31m-        //create log[m
         if($result->status){[m
             $this->changeStatus($this->getBeforeOrderStatus());[m
         }else{[m
[31m-            $this->changeStatus(Order::STATE_HOLDED);[m
[32m+[m[32m            $this->changeStatus(Order::STATE_CANCELED);[m
         }[m
         return $result;[m
 [m
[36m@@ -205,9 +204,7 @@[m [mclass Redirect extends \Magento\Framework\View\Element\Template[m
                 $result->msg = 'تراکنش ناموفق بود. در صورت کسر وجه، مبلغ تا 72 ساعت به حساب شما بر می گردد.'."\n"."علت: ".$response['message'];[m
             }[m
             if ($result->state) {[m
[31m-                if($this->getAfterOrderStatus()!=Order::STATE_CANCELED){[m
[31m-                    $this->changeStatus($this->getAfterOrderStatus());[m
[31m-                }[m
[32m+[m[32m                $this->changeStatus($this->getAfterOrderStatus());[m
             } else {[m
                 $this->changeStatus(Order::STATE_CANCELED);[m
             }[m
[1mdiff --git a/README.md b/README.md[m
[1mindex 463e78a..6d4c7da 100644[m
[1m--- a/README.md[m
[1m+++ b/README.md[m
[36m@@ -1,22 +1,21 @@[m
 # About The Project[m
 [m
 [m
[31m-this plugin enables packpay payment gateway for magento 2. please do not hesitate to contact [Packpay](https://packpay.ir/contact)[m
[32m+[m[32mThis plugin enables packpay payment gateway for magento 2. Please do not hesitate to contact [Packpay](https://packpay.ir/contact)[m
 if you have any questions.[m
 [m
[31m-This plugin is developed by packpay development team. more info at: [Packpay](https://lab.packpay.ir/)[m
[32m+[m[32mThis plugin is developed by packpay development team. More info at: [Packpay](https://lab.packpay.ir/)[m
 [m
 Tested on magento 2.2[m
 [m
 # Instalation[m
 [m
[31m-[m
[31m-in order to install this plugin, download and copy it under "app/code/Packpay/Gateway"(create Packpay and Gateway foulders).[m
[31m-after that run these commands in you magento root direcory:[m
[32m+[m[32mIn order to install this plugin, download and copy it under "app/code/Packpay/Gateway"(create Packpay and Gateway foulders).[m
[32m+[m[32mAfter that run these commands in your magento root direcory:[m
 1. ```php bin/magento setup:upgrade```[m
 2. ```php bin/magento setup:di:compile```[m
 3. ``` php bin/magento setup:static-content:deploy -f ```[m
 4. ```php bin/magento setup:di:compile```[m
 [m
[31m-if success you can see packpay setting at Store > Configuration > Sales > Payment methods[m
[32m+[m[32mIf success, you can see packpay setting at Store > Configuration > Sales > Payment methods[m
 [m
