<?php

// prevent illegal run
if (! defined('IN_PLUGINS_SYSTEM'))
{
    exit;
}



class KJP
{
    /**
     * to get kleeja_payment plugin info
     * before using this class, check if(defined('support_kjPay')){ then use it }
     */
    public static function info()
    {
        global $SQL , $dbprefix;
        $KJP_INFO = Plugins::getInstance()->installed_plugin_info('kleeja_payment');

        // get the id from the DB

        $KJP_INFO['plugin_id'] = $SQL->fetch(
            $SQL->query("SELECT `plg_id` FROM {$dbprefix}plugins WHERE `plg_name` = 'kleeja_payment'")
        )['plg_id'];

        return $KJP_INFO;
    }

    /*
     * if another plugins used this plugin , it need to use some langes for user interface
     * but when the admin don't need that plugin , the plugin will remove the langs from DB
     * and KJP plugin can not display the payment info
     * so give me the langs , and i know when it have to be deleted
     */
    public static function addLang(array $KJP_langs, $language = 'ar')
    {
        global $olang;

        $KJP_ID = self::info()['plugin_id'];

        $new_langs = [];

        // insert every lang that is not exists

        foreach ($KJP_langs as $word => $translate)
        {
            if (! isset($olang[$word]))
            {
                $new_langs[$word] = $translate;
            }
        }
        add_olang($new_langs, $language, $KJP_ID);
    }

    public static function firstRun()
    {
        // becouse kleeja delete all content in the plugin when it update ,
        // we need to extract the SDK again with every update
        if (! file_exists(dirname(__FILE__) . '/../vendor/autoload.php'))
        {
            // extract paypal sdk
            if (file_exists(dirname(__FILE__) . '/../paypal_sdk.zip'))
            {
                $paypalZip = new ZipArchive;

                if ($paypalZip->open(dirname(__FILE__) . '/../paypal_sdk.zip'))
                {
                    $paypalZip->extractTo(dirname(__FILE__) . '/../');
                    $paypalZip->close();
                }
            }
        }

        if (! file_exists(dirname(__FILE__) . '/../stripe-sdk/vendor/autoload.php'))
        {
            // extract stripe sdk
            if (file_exists(dirname(__FILE__) . '/../stripe-sdk.zip'))
            {
                $stripeZip = new ZipArchive;

                if ($stripeZip->open(dirname(__FILE__) . '/../stripe-sdk.zip'))
                {
                    $stripeZip->extractTo(dirname(__FILE__) . '/../');
                    $stripeZip->close();
                }
            }
        }
    }
}
