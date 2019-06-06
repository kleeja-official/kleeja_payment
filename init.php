<?php
# kleeja plugin
# Kleeja Payment
# version: 1.0
# developer: Mitan Omar

# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit;
}

# plugin basic information
$kleeja_plugin['kleeja_payment']['information'] = array(
    # the casual name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'Kleeja Payment',
        'ar' => 'مدفوعات كليجا'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'Mitan Omar',
    # this plugin version
    'plugin_version' => '1.1',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Selling Files and Premium Groups',
        'ar' => 'بيع الملفات والمجموعات المميزة'
    ),

    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '3.1',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0 ,
    # setting page to display in plugins page
    'settings_page' => 'cp=options&smt=kleeja_payment'
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kleeja_payment']['first_run']['ar'] = "
باستخدام هذا البرنامج المساعد ، يمكنك تسعير الملفات والمجموعات لبيعها ، واستلام الدفعات إلى حساب paypal الخاص بك تلقائيًا <br>
قم يزيارة صفحة الساعدة للمزيد <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >الساعدة</a>

";

$kleeja_plugin['kleeja_payment']['first_run']['en'] = "
With this plugin you can pricing the files and groups to selling it , and recive the payments to your paypal account automaticly
( WebMasters Only ) <br>

for more info visit help page <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >Help</a>

";

# plugin installation function
$kleeja_plugin['kleeja_payment']['install'] = function ($plg_id) {

    global $SQL , $dbprefix , $d_groups;

    $SQL->query(
        "CREATE TABLE IF NOT EXISTS `{$dbprefix}payments` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `payment_state` text COLLATE utf8_bin NOT NULL,
        `payment_method` VARCHAR(100) NULL DEFAULT NULL,
        `payment_more_info` LONGTEXT NOT NULL,
        `payment_amount` float NOT NULL,
        `payment_currency` VARCHAR(10) NOT NULL,
        `payment_token` text COLLATE utf8_bin NOT NULL,
        `payment_payer_ip` text COLLATE utf8_bin NOT NULL,
        `payment_action` text COLLATE utf8_bin NOT NULL,
        `item_id` int(11) NOT NULL,
        `item_name` text COLLATE utf8_bin NOT NULL,
        `user` int(11) NOT NULL,
        `payment_year` int(11) NOT NULL,
        `payment_month` int(11) NOT NULL,
        `payment_day` int(11) NOT NULL,
        `payment_time` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

        $SQL->query(
            "CREATE TABLE IF NOT EXISTS `{$dbprefix}payments_out` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user` int(11) NOT NULL,
                `method` text COLLATE utf8_bin NOT NULL,
                `amount` float NOT NULL,
                `payment_more_info` text COLLATE utf8_bin NOT NULL,
                `payout_year` int(11) NOT NULL,
                `payout_month` int(11) NOT NULL,
                `payout_day` int(11) NOT NULL,
                `payout_time` text COLLATE utf8_bin NOT NULL,
                `state` text COLLATE utf8_bin NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );

    $SQL->query("ALTER TABLE `{$dbprefix}files` ADD `price` FLOAT NOT NULL DEFAULT '0';");
    $SQL->query("ALTER TABLE `{$dbprefix}users` ADD `balance` FLOAT NOT NULL DEFAULT '0.00';");

    // create group permission to access bought files

    foreach ($d_groups as $group_id => $group_info)
    {
        // guest & bought files => problems
        // search for "expected_err" on this document Ctrl + F
        // and u will know what i mean
        if ($group_id == 2)
        {
            continue;
        }
        // access_bought_files
        $insert_acl = array(
            'INSERT'	=> 'acl_name, acl_can, group_id',
            'INTO'		=> "{$dbprefix}groups_acl",
            'VALUES'	=>  "'access_bought_files', 0 , " . $group_id
        );
        $SQL->build($insert_acl);

        // recaive_profits
        $insert_acl = array(
            'INSERT'	=> 'acl_name, acl_can, group_id',
            'INTO'		=> "{$dbprefix}groups_acl",
            'VALUES'	=>  "'recaive_profits', 0 , " . $group_id
        );
        $SQL->build($insert_acl);
    }

    $options = array(
        'join_price' =>
            array(
                'value'  => '0',
                'html'   => configField('join_price'),
                'plg_id' => $plg_id,
                'type'   => 'groups',
                'order'  => '1',
            ),

            'pp_client_id' => 
            array(
                'value'  => '0',
                'html'   => configField('pp_client_id'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),

            'paypal_client_secret' => 
            array(
                'value'  => '0',
                'html'   => configField('paypal_client_secret'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'stripe_publishable_key' =>
            array(
                'value'  => '0',
                'html'   => configField('stripe_publishable_key'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'stripe_secret_key' =>
            array(
                'value'  => '0',
                'html'   => configField('stripe_secret_key'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'iso_currency_code' => 
            array(
                'value'  => 'USD',
                'html'   => configField('iso_currency_code'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'down_link_expire' =>
            array(
                'value'  => '1',
                'html'   => configField('down_link_expire'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'file_owner_profits' =>
            array(
                'value'  => '0',
                'html'   => configField('file_owner_profits'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'min_price_limit' =>
            array(
                'value'  => '1',
                'html'   => configField('min_price_limit'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'max_price_limit' =>
            array(
                'value'  => '5',
                'html'   => configField('max_price_limit'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),


    );

    // an example to add your method to kleeja payments
    // be sure that the type is ('kj_pay_active_mthd') and the name is (active_{$your_method})
    // check getPaymentMethods() function for more informations

    $options['active_paypal'] = array(
            'value'  => '1',
            'html'   => configField('active_paypal' , 'yesno'),
            'plg_id' => $plg_id,
            'type'   => 'kj_pay_active_mthd',

    );
    $options['active_cards'] = array(
            'value'  => '1',
            'html'   => configField('active_cards' , 'yesno'),
            'plg_id' => $plg_id,
            'type'   => 'kj_pay_active_mthd',
    );


    add_config_r($options);


        if ( ! file_exists( dirname(__FILE__) . '/vendor/autoload.php' ) )
        {
            // extract paypal sdk
            if (file_exists( dirname(__FILE__) . '/paypal_sdk.zip' ))
            {
                $paypalZip = new ZipArchive;
                if ( $paypalZip->open( dirname(__FILE__) . '/paypal_sdk.zip') )
                {
                    $paypalZip->extractTo( dirname(__FILE__) );
                    $paypalZip->close();
                }
            }


        }
        if ( ! file_exists( dirname(__FILE__) . '/stripe-sdk/vendor/autoload.php' ) )
        {
            // extract stripe sdk
            if (file_exists( dirname(__FILE__) . '/stripe-sdk.zip' ))
            {
                $stripeZip = new ZipArchive;
                if ( $stripeZip->open( dirname(__FILE__) . '/stripe-sdk.zip') )
                {
                    $stripeZip->extractTo( dirname(__FILE__) );
                    $stripeZip->close();
                }
            }


        }

};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kleeja_payment']['update'] = function ($old_version, $new_version) {
    // if(version_compare($old_version, '0.5', '<')){
    // 	//... update to 0.5
    // }
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# plugin uninstalling, function to be called at uninstalling
$kleeja_plugin['kleeja_payment']['uninstall'] = function ($plg_id) {

    global $SQL , $dbprefix;

    $SQL->query("ALTER TABLE `{$dbprefix}files` DROP `price`;");
    $SQL->query("ALTER TABLE `{$dbprefix}users` DROP `balance`;");

    // removed from db
    //delete_olang(null, null , $plg_id);

    delete_config(array(
        'join_price',
        'paypal_client_secret',
        'pp_client_id',
        'iso_currency_code',
        'payment_method',
        'active_paypal',
        'active_cards',
        'down_link_expire',
        'stripe_publishable_key',
        'stripe_secret_key',
        'min_price_limit',
        'max_price_limit'
    ));

    // DELETE ACCESS BOUGHT FILES PERMISSIONS AND recaive profits

    $SQL->query("DELETE FROM `{$dbprefix}groups_acl` WHERE acl_name = 'access_bought_files' OR acl_name = 'recaive_profits'");



};

# plugin functions
$kleeja_plugin['kleeja_payment']['functions'] = array(

    'qr_download_id_filename' => function ($args) {

        global $SQL , $config , $usrcp;

        $query = $args['query'];
        $query['SELECT'] .= ', f.price';

        $result = $SQL->build($query);

        if ($SQL->num_rows($result) > 0)
        {

            $row = $SQL->fetch_array($result);
                if ($row['price'] > 0)
                {
                    # wibsite founders and file Owner can download without pay

                    if ( $usrcp->get_data('founder')['founder'] == 0 && 
                     ($row['fuserid'] !== $usrcp->id() || $row['fusername'] !== $usrcp->name() ) )
                    {
                        redirect($config['siteurl'] . 'do.php?file=' . $row['id']);
                        $SQL->close();
                        exit;

                    }

                }
        }

    } ,

    'err_navig_download_page' => function($args)
    {
        global $config, $SQL, $dbprefix, $lang , $tpl , $THIS_STYLE_PATH_ABS;

        if (ig('file') && (int) g('file')) {

            // avilable Payment methods

            $payment_methods = array();
            foreach ( getPaymentMethods() as $value)
            {
                $value = trim($value);
                $payment_methods[$value] = array('name' => strtoupper($value) , 'method' => $value);
            }

            if (ip('buy_file')) {

                redirect($config['siteurl'] . 'go.php?go=kj_payment&method='.p('method').'&action=buy_file&id=' . g('file'));
                exit();
            }

            require_once dirname(__FILE__) . '/php/down_ui.php';

            // add Vars to $GLOBAL
            $error = false;
            $tpl->assign('id' ,$id);
            $tpl->assign('name' ,$name);
            $tpl->assign('real_filename' ,$real_filename);
            $tpl->assign('type' ,$type);
            $tpl->assign('time' ,$time);
            $tpl->assign('uploads' ,$uploads);
            $tpl->assign('price' ,$price);
            $tpl->assign('fusername' ,$fusername);
            $tpl->assign('REPORT' ,$REPORT);
            $tpl->assign('userfolder' ,$userfolder);
            $tpl->assign('size' ,$size);
            $tpl->assign('FormAction' ,$FormAction);
            $tpl->assign('is_style_supported' ,$is_style_supported);
            $tpl->assign('payment_methods' ,$payment_methods);

            Saaheader($title);
            echo $tpl->display($sty , $styPath);
            Saafooter();

            return compact('error');

            $SQL->close();

        }

    } ,

    'default_go_page' => function($args)
    {
        global $lang , $olang , $usrcp , $config , $THIS_STYLE_PATH_ABS;


        // request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=buy_file&id=1
        // action = buy_file OR join_group or check
        // id = the id of file or the id of group

        // checking request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=check&blablabla
        // blablabla = it's optional for you to using sessions or anythink you want , anyway it will be global varibles


        if ( ig('go') && g('go') === 'kj_payment' && ig('method') && ig('action') )
        {
            require_once dirname(__FILE__) .'/php/kjPayment.php'; // require the payment interface
            $PaymentMethodClass = dirname(__FILE__) . '/method/'.g('method').'.php'; // default payment method

            if ( ! file_exists( $PaymentMethodClass ) )
            {
                $is_err = true;
                is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:set_payment_method', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if ($is_err) 
                {
                    kleeja_err('The class file of '.g('method').' payment in not found');
                    exit;
                }

            }
            
            require_once $PaymentMethodClass;
            

            $PaymentMethod = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

            // to be sure
            if ($PaymentMethod !== 'kjPayMethod_' . g('method') )
            {
                kleeja_err('Its not your method');
                exit;
            }

            $PagePermission = 'createPayment';

            if ( ! $PaymentMethod::permission($PagePermission) )
            {
                kleeja_err('This Method Dont support Creating Payments');
                exit;
            }

            $PAY = new $PaymentMethod;

            $PAY->paymentStart(); // Play some song to enjoy ;

            $PAY->setCurrency( strtoupper($config['iso_currency_code']) );

            switch ( g('action') ) {
                case 'buy_file':

                    if ( ! ig('id') ) {
                        kleeja_err($lang['ERROR_NAVIGATATION']);
                        exit;
                    }

                    // user can't buy another file before receive the link of first file
                    // if the user do it , he will lost access to first bought file
                    if ($usrcp->kleeja_get_cookie('mailForDownFile'))
                    {
                    // the user didn't download the file , becuse he did n't set his e-mail
                    redirect( $config['siteurl'] . 'go.php?go=KJPaymentMailer' );
                    exit;
                    }

                    $fileInfo = getFileInfo( g('id') ); // get file information

                    if ($fileInfo['price'] <= 0 )
                    {
                        kleeja_err(' The File Is For Free ');
                        exit;
                    }

                    $PAY->CreatePayment( 'buy_file' , $fileInfo);

                    // get some vars for kleeja # compact(':)')
                    foreach ($PAY->varsForCreatePayment() as $varName => $varValue)
                    {
                        $GLOBALS[$varName] = $varValue;
                    }


                    break;

                case 'join_group':
                    # Joining Group Steps
                    if ( ! ig('id') ) {
                        kleeja_err($lang['ERROR_NAVIGATATION']);
                        exit;
                    }

                    $userIs = $usrcp->get_data('group_id');
                    if ( ! $usrcp->name() )  // the Guests have to signup befor join ..
                    {
                        /** $usrcp->id() == false && !$usrcp->id() this options does not work here */
                        kleeja_err( $lang['USER_PLACE'] );
                        exit;

                    }elseif ( $userIs['group_id'] == g('id') ) // if the user is in this group .. note : $usrcp->group_id() also was making problems here , the user need to lougout
                    {
                        kleeja_err( $olang['KJP_CNT_JOIN'] );
                        exit;

                    }else
                    {
                        $groupInfo = getGroupInfo($args['d_groups'] , g('id'));

                        if ($groupInfo)
                        {
                            $PAY->CreatePayment( 'join_group' , $groupInfo);

                            foreach ($PAY->varsForCreatePayment() as $varName => $varValue)
                            {
                                $GLOBALS[$varName] = $varValue;
                            }


                        }else {
                            kleeja_err("It's not allowed to you to join this group");
                            exit;
                        }
                    }

                    break;
                case 'check':
                    # Checking Payments steps

                    // i don't want the user reset the cookie expire date
                    if ($usrcp->kleeja_get_cookie('mailForDownFile'))
                    {
                        // the user didn't download the file , becuse he did n't set his e-mail
                        redirect( $config['siteurl'] . 'go.php?go=KJPaymentMailer' );
                        exit;
                    }

                    $PAY->checkPayment();

                    if ( $PAY->isSuccess() )
                    {
                        $GLOBALS['title'] = 'successful Payment ' . $_SESSION['kj_payment']['db_id'] ;
                        $GLOBALS['no_request'] = FALSE ;
                        $GLOBALS['stylee'] = 'pay_success' ;
                        $GLOBALS['FormAction'] = $config['siteurl'] . 'go.php?go=KJPaymentMailer' ;
                        // to allow the developers to including 'pay_success.html' with their styles .
                        $GLOBALS['styleePath'] = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/paypal.pay_success.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/html/';


                        $global_vars = $PAY->getGlobalVars(); // compact(':)')

                        foreach ($global_vars as $varName => $varValue)
                        {
                            $GLOBALS[$varName] = $varValue;
                        }

                        if ($_SESSION['kj_payment']['payment_action'] == 'buy_file') // we send e-mail only when the user buying files , no e-mail for joining group
                        {
                            // "expected_err"
                            if ( ! $usrcp->name() || ! user_can('access_bought_files') ) // the user can find the file on bought files , don't need to send the download link
                            {
                                if ($PAY->linkMailer()) // if the method support email
                                {
                                    $mailTemplate = str_replace( array('@fileName' , '@downLink' , '@linkExpire') , array($global_vars['file_name'] , $global_vars['down_link'] , date('Y-m-d / H:i:s' , ( $config['down_link_expire'] * 86400) + time() ) ) , $GLOBALS['olang']['KJP_MAIL_TPL']); // error here

                                    $mailer =  send_mail($PAY->linkMailer(), $mailTemplate, 'kleeja Payment Download Link', $config['sitemail'], $config['sitename']);
                                    if ($mailer) // mail is sent , don't need mail form & dispaly success msg
                                    {
                                        $GLOBALS['olang']['KJP_DOWN_INFO_2'] = str_replace( array('@mail' , '@time') , array($PAY->linkMailer() , date('Y-m-d / H:i:s' , ( $config['down_link_expire'] * 86400) + time() ) ) , $GLOBALS['olang']['KJP_DOWN_INFO_2'] );
                                        $GLOBALS['showMailForm'] = false ;
                                        $usrcp->kleeja_set_cookie('downloadFile_'.$_SESSION['kj_payment']['item_id'] ,$_SESSION['kj_payment']['item_id']. '_'. $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'] , ( $config['down_link_expire'] * 86400) + time() );
                                    }
                                    else // we have to send mail again , i hope we never never arrive to this part :(
                                    {
                                        $GLOBALS['showMailForm'] = true ;
                                        $GLOBALS['olang']['KJP_DOWN_INFO_2'] = ''; // dont show this msg , we didn't send it yet
                                        $usrcp->kleeja_set_cookie('mailForDownFile' ,$_SESSION['kj_payment']['item_id']. '_'. $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'] , time() + 86400 );
                                    }

                                }else // method don't support email -> display email form & hide msg & set coockie to use mailform page
                                {
                                    $GLOBALS['showMailForm'] = true ;
                                    $GLOBALS['olang']['KJP_DOWN_INFO_2'] = ''; // dont show this msg , we didn't send it yet
                                    $usrcp->kleeja_set_cookie('mailForDownFile' ,$_SESSION['kj_payment']['item_id']. '_'. $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'] , time() + 86400 );
                                }
                            }else
                            {
                                $GLOBALS['olang']['KJP_DOWN_INFO_2'] = 'you can see the file and all bought files on <a href="./ucp.php?go=bought_files">Bought Files </a> Page';
                                $GLOBALS['showMailForm'] = false ;
                                $usrcp->kleeja_set_cookie('downloadFile_'.$_SESSION['kj_payment']['item_id'] ,$_SESSION['kj_payment']['item_id']. '_'. $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'] , ( $config['down_link_expire'] * 86400) + time() );
                            }
                        }

                    }
                    unset($_SESSION['kj_payment']);
                    //else
                    //{
                        // Every method will do somthing , include it in $PAY->checkPayment()
                    //}
                    break;

                default:


                $request = false; // maybe we will need it later;

                is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:default_action', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if( ! $request)
                {
                    kleeja_err('Why i am here ??');
                }

                    break;
            }

        }elseif ( g('go') == 'paid_group') {

            if (ip('join_grp'))
            {
                // to be sure that no when playing with html file
                if (in_array( p('method') , getPaymentMethods() ))
                {
                    redirect($config['siteurl'] . 'go.php?go=kj_payment&method='.p('method').'&action=join_group&id=' . p('group_id'));
                    exit();
                }
            }


            $MethodOption = '';
            foreach ( getPaymentMethods() as $value)
            {

                $value = trim($value);

                $MethodOption .= "<option value='".$value."'>".strtoupper($value)."</option>\n";
                // loop inside loop doesn't work in kleeja styles
            }


            $no_request = false ;
            $stylee = 'paid_group';
            $titlee = 'Paid Group';
            $is_style_supported = is_style_supported();
            // to allow the developers to including 'paid_group.html' with their styles .
            $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/paid_group.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';

            $PaidGroups = getGroupInfo($args['d_groups']);

            return compact('no_request' , 'titlee', 'stylee' , 'styleePath' , 'PaidGroups' , 'MethodOption' , 'is_style_supported');
        }

        // Send Download Link
        elseif ( g('go') == 'KJPaymentMailer' )
        {
            $payCookieInfo  = $usrcp->kleeja_get_cookie('mailForDownFile');

            if (! $payCookieInfo)
            {
                // ! from check payment page or the mail is sent
                kleeja_err($lang['ERROR_NAVIGATATION']);
                exit;
            }

            $payCookieInfoExplode = explode('_' , $payCookieInfo);

            $fileName = getFileInfo($payCookieInfoExplode[0])['real_filename'];


            if (ip('sendMail'))
            {
                $mailAdress = p('buyerMail');
                if ( ! filter_var($mailAdress, FILTER_VALIDATE_EMAIL))
                {
                    kleeja_err('put right e-mail' , '' , true , $config['siteurl'] . 'go.php?go=KJPaymentMailer' , 2);
                    exit; // again :)
                }

                $downloadLink = $config['siteurl'] . 'do.php?downPaidFile=' . $payCookieInfoExplode[0] . '_'.$payCookieInfoExplode[1] . '_' . $payCookieInfoExplode[2];

                $mailTemplate = str_replace( array('@fileName' , '@downLink' , '@linkExpire') , array($fileName , $downloadLink , date('Y-m-d / H:i:s' , ( $config['down_link_expire'] * 86400) + time() ) ) , $GLOBALS['olang']['KJP_MAIL_TPL']);

                $mailer = send_mail($mailAdress, $mailTemplate, 'kleeja Payment Download Link', $config['sitemail'], $config['sitename']);
                if ( ! $mailer )
                {
                    kleeja_err('Error in sending e-mail , try again' , '' , true , $config['siteurl'] . 'go.php?go=KJPaymentMailer' , 3);
                    exit;
                }else
                {

                    // set cookie for download file
                    $usrcp->kleeja_set_cookie('downloadFile_' . $payCookieInfoExplode[0] , $payCookieInfo , ( $config['down_link_expire'] * 86400) + time() );


                    // delete cookie
                    $usrcp->kleeja_set_cookie( 'mailForDownFile' , 'Finaly done , :)' , time() - 86400); // *_*

                    // dispaly success msg || I HOPE WE DONE
                    kleeja_info(
                        str_replace( array('@mail' , '@time') , array($mailAdress , date('Y-m-d / H:i:s' , ( $config['down_link_expire'] * 86400) + time() ) ) , $GLOBALS['olang']['KJP_DOWN_INFO_2'] )
                    );

                }

            }

            $titlee = 'Download Mail Sender';
            $no_request = false;
            $FormAction = $config['siteurl'] . 'go.php?go=KJPaymentMailer';
            $is_style_supported = is_style_supported();
            $stylee = 'kjpayment_mailer';
            $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/kjpaymentmailer.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';
            return compact('stylee' , 'styleePath' , 'fileName', 'no_request' , 'is_style_supported');
        }


    } ,

    'qr_down_go_page_filename' => function ($args)
    {
        global $SQL , $usrcp , $config;

        $query = $args['query'];
        $query['SELECT'] .= ', f.price , f.user';
        $result = $SQL->build($query);

        if ($SQL->num_rows($result) > 0)
        {

            $row = $SQL->fetch_array($result);
                if ($row['price'] > 0)
                {
                    // i hate this part
                    $redirect = true ;

                    # wibsite founders and file Owner can download without pay
                    if (  $usrcp->get_data('founder')['founder'] == 0 && !( $row['user'] === $usrcp->id() )  )
                    {

                        if ( ig('downToken') && ig('db') )
                        {

                            $paymentInfo = getPaymentInfo(g('db') , 'item_id = "' . $row['id'] . '" AND payment_action = "buy_file" AND payment_state = "approved" AND payment_token = "'.g('downToken').'"');


                            if(  $paymentInfo )
                            {
                                if ( $config['down_link_expire'] > 0) // if 0 -> download link will never expire
                                {
                                    $downCookie = $usrcp->kleeja_get_cookie('downloadFile_'.g('down'));
                                    if ($downCookie)
                                    {
                                        $downCookie = explode('_' , $downCookie);
                                        if ( g('down') == $downCookie[0] && g('db') == $downCookie[1] && g('downToken') == $downCookie[2] )
                                        {
                                            $month = $paymentInfo['payment_month'];
                                            $day = $paymentInfo['payment_day'];
                                            $year = $paymentInfo['payment_year'];
                                            $payment_time = explode(':' , $paymentInfo['payment_time']);
                                            $hour = $payment_time[0];
                                            $minute = $payment_time[1];
                                            $seconde = $payment_time[2];
                                            $paymentTime = mktime($hour , $minute , $seconde  , $month , $day , $year);

                                            if ( ( ( $config['down_link_expire'] * 86400 ) + $paymentTime ) >= time() )
                                            {
                                                $redirect = false;
                                            }
                                        }
                                    }

                                }
                                else // $config['down_link_expire'] == 0 -> download link will never expire
                                {
                                    $redirect = false;
                                }
                            }

                        }

                    }else // the user is founder or file owner
                    {
                        $redirect = false;
                    }

                    if ( $redirect ) {

                        redirect($config['siteurl'] . 'do.php?file=' . $row['id']);
                        $SQL->close();
                        exit;

                    }
                }
        }
    } ,

    'begin_admin_page' => function ($args)
    {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];
        $adm_extensions[] = 'kj_payment_options';
        $ext_icons['kj_payment_options'] = 'money';
        return compact('adm_extensions', 'ext_icons');
    } ,

    'not_exists_kj_payment_options' => function ($args)
    {
        $include_alternative = dirname(__FILE__) . '/php/kj_payment_options.php';
        return compact('include_alternative');
    } ,

    'Saaheader_links_func' => function ($args)
    {
        global $d_groups , $config;
        $top_menu = $args['top_menu'];
        $side_menu = $args['side_menu'];
        $user_is = $args['user_is'];

        $side_menu[] = array('name' => 'bought_files', 'title' => $args['olang']['KJP_BOUGHT_FILES'], 'url' => $config['siteurl'] . 'ucp.php?go=bought_files', 'show' => ($user_is && user_can('access_bought_files') ? true : false ));
        $side_menu[] = array('name' => 'my_kj_payment', 'title' => 'Payments Control', 'url' => $config['siteurl'] . 'ucp.php?go=my_kj_payment', 'show' => ($user_is && user_can('recaive_profits') ? true : false ));
        $top_menu[] = array('name' => 'paid_group', 'title' => $args['olang']['KJP_PID_GRP'], 'url' => 'go.php?go=paid_group', 'show' => getGroupInfo($d_groups));

        return compact('top_menu' , 'side_menu');
    } ,


    'begin_download_page'  => function ($args)
    {
        global $config , $usrcp;
        if ( ig('downPaidFile') ) // the mailed link to Buyer mail
        {

            // EX: domain.io/kleeja/go.php?downPaidFile=fileID_dbID_payToken
            $downToken = explode('_' , g('downPaidFile'));
            $fileID    = $downToken[0];
            $dbID      = $downToken[1];
            $payToken  = $downToken[2];

            $paymentInfo = getPaymentInfo( $dbID , "item_id = '{$fileID}' AND payment_token = '{$payToken}' AND payment_state = 'approved' AND payment_action = 'buy_file'");

            if ( $paymentInfo )
            {
                // for this session i made this page
                $_SESSION['HTTP_REFERER'] =  $fileID;

                redirect( $config['siteurl'] . 'do.php?down=' . $fileID . '&amp;db='.$dbID . '&amp;downToken=' . $payToken );
                exit;
            }else
            {
                redirect( $config['siteurl'] ); //OR kleeja_err();
            }

            exit;

        }
    },
    'default_usrcp_page' => function ($args)
    {
        global $SQL , $dbprefix , $usrcp , $config ,$olang , $userinfo;
        // all user bought file
        if ( g('go') == 'bought_files')
        {

            if( ! $usrcp->name() || ! user_can('access_bought_files') ): return ; endif; // the page is for members only

            $user_is = $usrcp->id();

            $titlee       = $olang['KJP_BOUGHT_FILES'];
            $no_request   = flase;
            $stylee       = 'bought_files';
            $is_style_supported = is_style_supported();
            $styleePath   = $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/bought_files.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';
            $havePayments = false;

            $query = array(
                'SELECT' => 'id , payment_token , item_name, item_id , payment_currency , payment_amount, payment_year , payment_month , payment_day , payment_time' ,
                'FROM' => $dbprefix . 'payments' ,
                'WHERE' => "payment_state = 'approved' AND user = '{$user_is}' AND payment_action = 'buy_file'" ,
                'ORDER BY' => 'id DESC'
            );

            $all_payments = $SQL->build($query);

            if ($SQL->num_rows($all_payments))
            {
                $myPayments = array();
                $havePayments = true;

                while ($pay = $SQL->fetch($all_payments))
                {
                    $myPayments[] = array(
                        'ID' => $pay['id'] ,
                        'FILE' => $pay['item_name'] ,
                        'AMOUNT' => $pay['payment_amount'] . ' ' . $pay['payment_currency'],
                        'DATE_TIME' => $pay['payment_day'] . '-' . $pay['payment_month'] . '-' . $pay['payment_year'] . ' / ' . $pay['payment_time'] ,
                        'DOWN_LINK' => $config['siteurl'] . 'do.php?downPaidFile='.$pay['item_id'].'_'.$pay['id'].'_'.$pay['payment_token']
                    );
                }
            }



            return compact( 'is_style_supported' ,'titlee' , 'no_request' , 'stylee' , 'styleePath' , 'myPayments' , 'havePayments');

        }
        // Payment UCP 
        elseif (g('go') == 'my_kj_payment')
        {
            if (!user_can('recaive_profits')) // this is not Guests page
            {
                return;
            }
            $action = $config['siteurl'] . 'ucp.php?go=my_kj_payment'; // for withdraw form
            $case = ig('case') ? g('case') : 'cp';
            $user_balance = $usrcp->get_data('balance')['balance'] . ' ' . strtoupper($config['iso_currency_code']); // to have it fresh
            $username = $usrcp->name();
            $user_id  = $usrcp->id();
            $titlee       = 'KJ Payment CP';
            $no_request   = flase;
            $stylee       = 'my_kj_payment';
            $is_style_supported = is_style_supported();
            $styleePath   = $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/my_kj_payment.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';

            // request your money
            if (ip('requestAmount'))
            {
                require_once dirname(__FILE__) .'/php/kjPayment.php'; // require the payment interface
                $PaymentMethodClass = dirname(__FILE__) . '/method/'.p('PayoutMethod').'.php'; // default payment method
    
                if ( ! file_exists( $PaymentMethodClass ) )
                {
                    $is_err = true;
                    is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:createPayout', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    
                    if ($is_err) 
                    {
                        kleeja_admin_err('The class file of '.p('PayoutMethod').' payment is not found');
                        exit;
                    }
    
                }
                require_once $PaymentMethodClass;
    
                $methodClassName = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

                if (! $methodClassName::permission('createPayout'))
                {
                    kleeja_err('The method dont support Creating Payouts');
                    exit;exit();exit;
                }
                // if erro password stop
                if (empty(p('userPass')) || ! $usrcp->kleeja_hash_password(p('userPass') . $userinfo['password_salt'], $userinfo['password']))
                {
                    kleeja_err('your password is not correct');
                    exit;
                }
                else
                {
                    // the password was correct , lets check the amount number
                    $requestAmount = (float) p('AmountNumber');
                    // is he really have this amount in hes balance
                    if ($requestAmount < 0 || $requestAmount > $user_balance)
                    {
                        // no -> he don't have it
                        kleeja_err('you can not request this amount from your balance or the number is not vailed Amount');
                        exit;
                    }
                    else
                    {
                        ! (float) $requestAmount ? kleeja_err('you can not request this amount from your balance or the number is not vailed Amount') : null;
                        $method   = 'paypal';
                        $amount   = $requestAmount;
                        $state    = 'verify';
                        $payout_year      = date("Y");
                        $payout_month     = date("m");
                        $payout_day       = date("d");
                        $payout_time      = date("H:i:s");
                        $payment_more_info = payment_more_info('to_db' , [
                            'SENDTO' => $usrcp->mail(),
                        ]);
    
                        $query = [
                            'INSERT'	=> 'user , payment_more_info , method , amount , state , payout_year , payout_month , payout_day , payout_time ',
                            'INTO'		=> "{$dbprefix}payments_out",
                            'VALUES'	=> "'{$user_id}' , '{$payment_more_info}' , '{$method}' , '{$amount}' , '{$state}' , '{$payout_year}' , '{$payout_month}' , '{$payout_day}' , '{$payout_time}'"
                        ];
                        
                        $SQL->build($query);
    
                        if ($SQL->affected())
                        {
                            $new_balance = $user_balance - $requestAmount;
                            $SQL->query("UPDATE {$dbprefix}users SET `balance` = '{$new_balance}' WHERE `name` = '{$username}'");
                            kleeja_info("the amount {$requestAmount} is requested , it will be in your account in 24 hour
                            <br> your new balance is {$new_balance}");
                        }
                    }
                }

            }

            if ($case == 'Requested_Amounts')
            {
                // only if the case is (Requested_Amounts) we will call this query
                $query = [
                    'SELECT' => '*' ,
                    'FROM'   => "{$dbprefix}payments_out",
                    'WHERE'  => "`user` = '{$user_id}'",
                    'ORDER BY' => 'id DESC'
                ];

                $result = $SQL->build($query);
                $havePayout = false;
                if ($num_rows = $SQL->num_rows($result))
                {
                    $perpage	  	= 21;
                    $currentPage	= ig('page') ? g('page', 'int') : 1;
                    $Pager			= new Pagination($perpage, $num_rows, $currentPage);
                    $start			= $Pager->getStartRow();
                    $linkgoto       = $cinfig['siteurl'] . 'ucp.php?go=my_kj_payment&case=Requested_Amounts';
                    $page_nums		= $Pager->print_nums( $linkgoto );
                    $query['LIMIT'] = "$start, $perpage";
                    $result = $SQL->build($query);


                    $payouts = [];
                    $havePayout = true;
                    while ($row = $SQL->fetch_array($result))
                    {
                        $payouts[] = [
                            'ID' => $row['id'],
                            'METHOD' => $row['method'],
                            'AMOUNT' => $row['amount'] . ' ' . $config['iso_currency_code'],
                            'DATE_TIME' => "{$row['payout_year']}-{$row['payout_month']}-{$row['payout_day']} / {$row['payout_time']}",
                            'STATE' => $row['state']
                        ];
                    }
                }
            }
            else if ($case == 'files_payments')
            {
                $fileQuery = [
                    'SELECT' => 'p.id , p.payment_method , p.item_name , p.item_id , p.user , p.payment_year , p.payment_month , p.payment_day , p.payment_time',
                    'FROM'   => "{$dbprefix}payments p",
                    'JOINS'   => 
                    [
                        [
                            'INNER JOIN' => "{$dbprefix}files f",
                            'ON'         => "p.item_id = f.id"
                        ]
                    ],
                    'WHERE'  => "f.user = ".$usrcp->id()." AND p.payment_action = 'buy_file'",
                    'ORDER BY'  => 'p.id DESC',
                ];

                $filePay = $SQL->build($fileQuery);

                $havePayments = false;
                if ($num_rows = $SQL->num_rows($filePay))
                {
                    $perpage	  	= 21;
                    $currentPage	= ig('page') ? g('page', 'int') : 1;
                    $Pager			= new Pagination($perpage, $num_rows, $currentPage);
                    $start			= $Pager->getStartRow();
                    $linkgoto       = $cinfig['siteurl'] . 'ucp.php?go=my_kj_payment&case=files_payments';
                    $page_nums		= $Pager->print_nums( $linkgoto );
                    $fileQuery['LIMIT'] = "$start, $perpage";
                    $filePay = $SQL->build($fileQuery);

                    $UserById = UserById();


                    $payments = [];
                    $havePayments = true;
                    while ($row = $SQL->fetch_array($filePay))
                    {
                        $payments[] = [
                            'ID' => $row['id'],
                            'METHOD' => $row['payment_method'],
                            'FILE_NAME' => $row['item_name'],
                            'BUYER' => ! empty($UserById[$row['user']]) ? $UserById[$row['user']] : 'Guest',
                            'DATE_TIME' => "{$row['payment_year']}-{$row['payment_month']}-{$row['payment_day']} / {$row['payment_time']}",
                        ];
                    }
                }


            }
            else if ($case == 'pricing_file')
            {
                if ( ip('open_file') ) {

		
                    $select_file_id =  ip('select_file_id') ? p('select_file_id') : null  ; 
            
                    $ExampleID = $config['siteurl'] . 'do.php?id=';
                    $ExampleIMG = $config['siteurl'] . 'do.php?img=';
            
                    ! (int) $select_file_id ? $select_file_id = str_replace(array($ExampleID , $ExampleIMG) , '' , $select_file_id) : $select_file_id ;
            
                    if ( $select_file_id !== null && $select_file_id > 0 && $file_info = getFileInfo($select_file_id))
                    {
                        if ($file_info['user'] == $usrcp->id()) // no be sure that every user will change hes files only
                        {
                            $show_price_panel = true;
                            $FileID = $file_info['id'];
                            $FileName = $file_info['real_filename'];
                            $FileSize = readable_size($file_info['size']);
                            $FileUser = $usrcp->name();
                            $FilePrice = $file_info['price'];
                        }
                        else
                        {
                            $OpenAlert = true;
                            $AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] .' '. $select_file_id;
                            $AlertRole = 'danger';
                        }

            
                    }
            
            
                }elseif ( ip('set_price') ) {
            
                    $FileID = (int) p('price_file_id');
                    $FileName = p('file_name') ;
                    $FilePrice = p('price_file');

                    if ($FilePrice < $config['min_price_limit'] || $FilePrice > $config['max_price_limit'])
                    {
                        kleeja_err(sprintf($olang['KJP_PRC_LMT'] , $config['min_price_limit'] , $config['max_price_limit'] , $config['iso_currency_code']));
                        exit;
                    }
            
                    if ( $file_info = getFileInfo( $FileID ) ) 
                    {
                        $update_query = array(
                            'UPDATE' => $dbprefix . 'files' ,
                            'SET'    => "price = '{$FilePrice}'" ,
                            'WHERE'  => "id = '{$FileID}' AND real_filename = '{$FileName}' AND user = ".$usrcp->id()
                        );
            
                        $SQL->build( $update_query );
            
                        if ($SQL->affected()) {
                            
                            $OpenAlert = true;
                            $AlertMsg = sprintf($olang['KJP_NO_FILE_NEW_PRICE'] ,$FileName, $FilePrice ,strtoupper($config['iso_currency_code'])) ;
                            $AlertRole = 'success';
                        }else {
                            $OpenAlert = true;
                            $AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] .' '. $FileID;
                            $AlertRole = 'danger';
                        }
                    }
                }
            }
            return compact('AlertRole','AlertMsg','OpenAlert',
                'show_price_panel','FileID', 'FileName','FileUser','FilePrice','FileSize',
                'havePayments','payments','page_nums','havePayout','payouts',
            'case','action','titlee' , 'no_request' , 'stylee' , 'styleePath' , 'user_balance');
        }
    } ,

    'login_data_no_error' => function ($args)
    {
        // after login , if the admin change the permission , the user have to login again to take the cookies
        if ( user_can('access_bought_files') ) :

        # success login , get all payment that user made it , and set the cookie to have access to download page
        global $usrcp , $SQL , $dbprefix;

        // $user_id = $usrcp->id(); doesn't work :( , i don't know why

        $username = $usrcp->name();
        $user_id = $SQL->fetch( $SQL->query("SELECT id FROM {$dbprefix}users WHERE `name` LIKE '{$username}'") );
        $user_id = $user_id['id'];

        $query = array(
            'SELECT' => 'id , payment_token , item_id',
            'FROM' => $dbprefix . 'payments' ,
            'WHERE' => "payment_state = 'approved' AND user = {$user_id} AND payment_action = 'buy_file'" ,
        );

        $boughtFiles = $SQL->build($query);

        if ($SQL->num_rows( $boughtFiles ) ) // if we have payments
        {
            while ($payInfo = $SQL->fetch( $boughtFiles ) )
            {
                $usrcp->kleeja_set_cookie('downloadFile_'.$payInfo['item_id'] ,$payInfo['item_id']. '_'. $payInfo['id'] . '_' . $payInfo['payment_token'] , time() + (86400 * 31) );
            }
        }

        endif;
    } ,

    'begin_logout' => function ($args)
    {
        // delete the cookies of bought files
        // exacly like we made in login steps , only change the expire data from after 1 month to befor 1 month :)
        global $usrcp , $SQL , $dbprefix;

        $user_id = $usrcp->id();

        $query = array(
            'SELECT' => 'id , payment_token , item_id',
            'FROM' => $dbprefix . 'payments' ,
            'WHERE' => "payment_state = 'approved' AND user = {$user_id} AND payment_action = 'buy_file'" ,
        );

        $boughtFiles = $SQL->build($query);

        if ($SQL->num_rows( $boughtFiles ) ) // if we have payment
        {
            while ($payInfo = $SQL->fetch( $boughtFiles ) )
            {
                $usrcp->kleeja_set_cookie('downloadFile_'.$payInfo['item_id'] ,$payInfo['item_id']. '_'. $payInfo['id'] . '_' . $payInfo['payment_token'] , time() - (86400 * 31) );
            }
        }
    } ,

    'boot_common' => function ($args)
    {
        global $olang , $config;
        define('support_kjPay' , true);

        // to check if the plugin is installed and enabled
        if (defined('support_kjPay'))
        {
            # a payment without salt please
        }
        $langFiles = dirname(__FILE__) . "/language/{$config['language']}.php";
        if (file_exists($langFiles))
        {
            $langFiles = require_once $langFiles;
            foreach ($langFiles as $key => $value)
            {
                $olang[$key] = $value;
            }
        }
        return compact('olang');
    }

    /*
    //Example
    'kjPay:add_to_panels' => function ($args)
    {
        $all_trnc_panel = $args['all_trnc_panel'];
        $all_trnc_panel[] = array( 'methodName' => 'PayPal' , 'htmlContent' => 'Hello From PayPal Method' );
        return compact('all_trnc_panel');
    }
    */


);


// Plugin Functions;
require_once dirname(__FILE__) . '/php/function.php';
