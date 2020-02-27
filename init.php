<?php
// kleeja plugin
// developer: KLEEJA TEAM

// prevent illegal run
if (! defined('IN_PLUGINS_SYSTEM'))
{
    exit;
}

// plugin basic information
$kleeja_plugin['kleeja_payment']['information'] = [
    // the casual name of this plugin, anything can a human being understands
    'plugin_title' => [
        'en' => 'Kleeja Payment',
        'ar' => 'مدفوعات كليجا'
    ],
    // who wrote this plugin?
    'plugin_developer' => 'Kleeja Team',
    // this plugin version
    'plugin_version' => '1.2.6',
    // explain what is this plugin, why should i use it?
    'plugin_description' => [
        'en' => 'Selling Files and Premium Groups',
        'ar' => 'بيع الملفات والمجموعات المميزة'
    ],

    // min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '3.1.5',
    // max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    // should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 10 , // only for define support_kjPay
    // setting page to display in plugins page
    'settings_page' => 'cp=options&smt=kleeja_payment'
];

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kleeja_payment']['first_run']['ar'] = "
باستخدام هذا البرنامج المساعد ، يمكنك تسعير الملفات والمجموعات لبيعها ، واستلام الدفعات إلى حساب paypal الخاص بك تلقائيًا <br>
قم يزيارة صفحة المساعدة للمزيد <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >المساعدة</a>

";

$kleeja_plugin['kleeja_payment']['first_run']['en'] = "
With this plugin you can sell & purchases files and also join groups, and receive the payments to your paypal account automaticly
<br>

for more info visit help page <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >Help</a>

";

// plugin installation function
$kleeja_plugin['kleeja_payment']['install'] = function ($plg_id) {
    global $SQL , $dbprefix , $d_groups;

    $InstallQuerys = [
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
            )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;" ,

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;" ,

        "CREATE TABLE IF NOT EXISTS `{$dbprefix}subscriptions` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` text COLLATE utf8_bin NOT NULL,
                `days` int(11) NOT NULL,
                `price` float NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;" ,

        "ALTER TABLE `{$dbprefix}files` ADD `price` FLOAT NOT NULL DEFAULT '0';" ,

        "ALTER TABLE `{$dbprefix}users` ADD `package` INT NOT NULL DEFAULT '0';" ,

        "ALTER TABLE `{$dbprefix}users` ADD `balance` FLOAT NOT NULL DEFAULT '0.00';" ,

        "ALTER TABLE `{$dbprefix}users` ADD `subs_point` INT NOT NULL DEFAULT '0';" ,

        "ALTER TABLE `{$dbprefix}users` ADD `package_expire` INT NOT NULL DEFAULT '0';" ,

        "CREATE TABLE `{$dbprefix}subscription_point` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user` int(11) NOT NULL,
            `file_id` int(11) NOT NULL,
            `subscription_id` int(11) NOT NULL,
            `subscripe_hash` text COLLATE utf8_bin NOT NULL,
            `time` int(11) NOT NULL,
            PRIMARY KEY (`id`)
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin"
    ];

    foreach ($InstallQuerys as  $query)
    {
        $SQL->query($query);
    }

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
        $insert_acl = [
            'INSERT'       => 'acl_name, acl_can, group_id',
            'INTO'         => "{$dbprefix}groups_acl",
            'VALUES'       => "'access_bought_files', 0 , " . $group_id
        ];
        $SQL->build($insert_acl);

        // recaive_profits
        $insert_acl['VALUES'] = "'recaive_profits', 0 , " . $group_id;
        $SQL->build($insert_acl);
    }

    $options = [
        'kjp_join_price' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_join_price'),
            'plg_id' => $plg_id,
            'type'   => 'groups',
            'order'  => '0',
        ],

        'kjp_min_payout_limit' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_min_payout_limit'),
            'plg_id' => $plg_id,
            'type'   => 'groups',
            'order'  => '1',
        ],

        'kjp_active_subscriptions' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_active_subscriptions', 'yesno'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '0',
        ],

        'kjp_active_live_mode' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_active_live_mode', 'yesno'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '0',
        ],

        'kjp_paypal_client_id' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_paypal_client_id'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '1',
        ],

        'kjp_paypal_client_secret' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_paypal_client_secret'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '2',
        ],

        'kjp_stripe_publishable_key' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_stripe_publishable_key'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '3',
        ],

        'kjp_stripe_secret_key' =>
        [
            'value'  => '0',
            'html'   => configField('kjp_stripe_secret_key'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '4',
        ],

        'kjp_iso_currency_code' =>
        [
            'value'  => 'USD',
            'html'   => configField('kjp_iso_currency_code'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '5',
        ],

        'kjp_down_link_expire' =>
        [
            'value'  => '1',
            'html'   => configField('kjp_down_link_expire'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '6',
        ],

        'kjp_file_owner_profits' =>
        [
            'value'  => '50',
            'html'   => configField('kjp_file_owner_profits'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '7',
        ],

        'kjp_min_price_limit' =>
        [
            'value'  => '1',
            'html'   => configField('kjp_min_price_limit'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '9',
        ],

        'kjp_max_price_limit' =>
        [
            'value'  => '5',
            'html'   => configField('kjp_max_price_limit'),
            'plg_id' => $plg_id,
            'type'   => 'kleeja_payment',
            'order'  => '8',
        ]
    ];

    // an example to add your method to kleeja payments
    // be sure that the type is ('kj_pay_active_mthd') and the name is (active_{$your_method})
    // check getPaymentMethods() function for more informations

    $options['kjp_active_paypal'] = [
        'value'  => '1',
        'html'   => configField('kjp_active_paypal', 'yesno'),
        'plg_id' => $plg_id,
        'type'   => 'kj_pay_active_mthd',
        'order'  => '1',

    ];

    $options['kjp_active_cards'] = [
        'value'  => '1',
        'html'   => configField('kjp_active_cards', 'yesno'),
        'plg_id' => $plg_id,
        'type'   => 'kj_pay_active_mthd',
        'order'  => '2',
    ];

    $options['kjp_active_balance'] = [
        'value'  => '1',
        'html'   => configField('kjp_active_balance', 'yesno'),
        'plg_id' => $plg_id,
        'type'   => 'kj_pay_active_mthd',
        'order'  => '0',
    ];


    add_config_r($options);

    // extract libs
    KJP::firstRun();
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kleeja_payment']['update'] = function ($old_version, $new_version) {
    global $SQL , $dbprefix;

    // extract Libs
    KJP::firstRun();

    if (version_compare($old_version, '1.2.4', '<'))
    {
        // create subscription table
        $SQL->query(
            "CREATE TABLE IF NOT EXISTS `{$dbprefix}subscriptions` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` text COLLATE utf8_bin NOT NULL,
                `days` int(11) NOT NULL,
                `price` float NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
        );
        $SQL->query(
            "CREATE TABLE `{$dbprefix}subscription_point` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user` int(11) NOT NULL,
                `file_id` int(11) NOT NULL,
                `subscription_id` int(11) NOT NULL,
                `subscripe_hash` text COLLATE utf8_bin NOT NULL,
                `time` int(11) NOT NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin"
        );
        // add package colum to users table
        $SQL->query("ALTER TABLE `{$dbprefix}users` ADD `package` INT NOT NULL DEFAULT '0';");
        $SQL->query("ALTER TABLE `{$dbprefix}users` ADD `package_expire` INT NOT NULL DEFAULT '0';");
        $SQL->query("ALTER TABLE `{$dbprefix}users` ADD `subs_point` INT NOT NULL DEFAULT '0';");


        //we need the id of kleeja_payment plugin

        $plg_id = KJP::info()['plugin_id'];

        // Add new Config for Subscriptions and add prefix to plugins setting

        add_config_r([
            'kjp_join_price' =>
            [
                'value'  => '0',
                'html'   => configField('kjp_join_price'),
                'plg_id' => $plg_id,
                'type'   => 'groups',
                'order'  => '0',
            ],

            'kjp_min_payout_limit' =>
            [
                'value'  => '0',
                'html'   => configField('kjp_min_payout_limit'),
                'plg_id' => $plg_id,
                'type'   => 'groups',
                'order'  => '1',
            ],

            'kjp_active_live_mode' =>
            [
                'value'  => '0',
                'html'   => configField('kjp_active_live_mode', 'yesno'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '0',
            ],

            'kjp_paypal_client_id' =>
            [
                'value'  => get_config('pp_client_id'),
                'html'   => configField('kjp_paypal_client_id'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '1',
            ],

            'kjp_paypal_client_secret' =>
            [
                'value'  => get_config('paypal_client_secret'),
                'html'   => configField('kjp_paypal_client_secret'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '2',
            ],

            'kjp_stripe_publishable_key' =>
            [
                'value'  => get_config('stripe_publishable_key'),
                'html'   => configField('kjp_stripe_publishable_key'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '3',
            ],

            'kjp_stripe_secret_key' =>
            [
                'value'  => get_config('stripe_secret_key'),
                'html'   => configField('kjp_stripe_secret_key'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '4',
            ],

            'kjp_iso_currency_code' =>
            [
                'value'  => get_config('iso_currency_code'),
                'html'   => configField('kjp_iso_currency_code'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '5',
            ],

            'kjp_down_link_expire' =>
            [
                'value'  => get_config('down_link_expire'),
                'html'   => configField('kjp_down_link_expire'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '6',
            ],

            'kjp_file_owner_profits' =>
            [
                'value'  => get_config('file_owner_profits'),
                'html'   => configField('kjp_file_owner_profits'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '7',
            ],

            'kjp_min_price_limit' =>
            [
                'value'  => get_config('min_price_limit'),
                'html'   => configField('kjp_min_price_limit'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '9',
            ],

            'kjp_max_price_limit' =>
            [
                'value'  => get_config('max_price_limit'),
                'html'   => configField('kjp_max_price_limit'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '8',
            ],

            'kjp_active_subscriptions' =>
            [
                'value'  => '0',
                'html'   => configField('kjp_active_subscriptions', 'yesno'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
                'order'  => '0',
            ],

            'kjp_active_balance'=>
            [
                'value'  => get_config('active_balance'),
                'html'   => configField('kjp_active_balance', 'yesno'),
                'plg_id' => $plg_id,
                'type'   => 'kj_pay_active_mthd',
                'order'  => '0',
            ],

            'kjp_active_paypal'=>
            [
                'value'  => get_config('active_paypal'),
                'html'   => configField('kjp_active_paypal', 'yesno'),
                'plg_id' => $plg_id,
                'type'   => 'kj_pay_active_mthd',
                'order'  => '0',
            ],

            'kjp_active_cards'=>
            [
                'value'  => get_config('active_cards'),
                'html'   => configField('kjp_active_cards', 'yesno'),
                'plg_id' => $plg_id,
                'type'   => 'kj_pay_active_mthd',
                'order'  => '0',
            ],
        ]);
        // we will delete the old configs , becouse we will add prefix to it

        delete_config([
            'join_price',
            'min_payout_limit',
            'paypal_client_secret',
            'pp_client_id',
            'iso_currency_code',
            'active_paypal',
            'active_cards',
            'active_balance',
            'down_link_expire',
            'stripe_publishable_key',
            'stripe_secret_key',
            'min_price_limit',
            'max_price_limit' ,
            'file_owner_profits' ,
        ]);
    }

    //you could use update_config
};


// plugin uninstalling, function to be called at uninstalling
$kleeja_plugin['kleeja_payment']['uninstall'] = function ($plg_id) {
    global $SQL , $dbprefix;

    $SQL->query("DROP TABLE `{$dbprefix}subscription_point`");
    $SQL->query("ALTER TABLE `{$dbprefix}files` DROP `price`;");
    $SQL->query("ALTER TABLE `{$dbprefix}users` DROP `balance` , DROP `package` , DROP `package_expire` , DROP `subs_point`;");

    // removed from db
    //delete_olang(null, null , $plg_id);

    delete_config([
        'kjp_join_price',
        'kjp_min_payout_limit',
        'kjp_paypal_client_secret',
        'kjp_paypal_client_id',
        'kjp_iso_currency_code',
        'kjp_payment_method',
        'kjp_active_paypal',
        'kjp_active_cards',
        'kjp_active_balance',
        'kjp_down_link_expire',
        'kjp_stripe_publishable_key',
        'kjp_stripe_secret_key',
        'kjp_min_price_limit',
        'kjp_max_price_limit' ,
        'kjp_active_subscriptions',
        'kjp_active_live_mode',
        'kjp_file_owner_profits'
    ]);

    // DELETE ACCESS BOUGHT FILES PERMISSIONS AND recaive profits

    $SQL->query("DELETE FROM `{$dbprefix}groups_acl` WHERE acl_name = 'access_bought_files' OR acl_name = 'recaive_profits'");

    // in the end , let's delete the olang , not our one , for other packages of this plugin

    delete_olang(null, null, $plg_id);
};

// plugin functions
$kleeja_plugin['kleeja_payment']['functions'] = [

    'qr_download_id_filename' => function ($args) {
        global $SQL , $config , $usrcp , $subscription , $olang;

        $query = $args['query'];
        $query['SELECT'] .= ', f.price';

        $result = $SQL->build($query);

        if ($SQL->num_rows($result) > 0)
        {
            $row = $SQL->fetch_array($result);

            if ($config['kjp_active_subscriptions'])
            { // if the subscription is active
                if ($row['price'] > 0 && ! $subscription->is_valid($usrcp->id()))
                { // subscripe is active but not valid & paid file

                    // wibsite founders and file Owner can download without pay
                    if ($usrcp->get_data('founder')['founder'] == 0 &&
                         ($row['fuserid'] !== $usrcp->id() || $row['fusername'] !== $usrcp->name()))
                    { // send to buy page && and display subscripes packs
                        redirect($config['siteurl'] . 'do.php?file=' . $row['id']);
                        $SQL->close();

                        exit;
                    }
                }
                elseif ($row['price'] == 0 && ! $subscription->is_valid($usrcp->id()))
                { // subscripe is active but not valid & free file

                    // wibsite founders and file Owner can download without pay
                    if ($usrcp->get_data('founder')['founder'] == 0 &&
                         ($row['fuserid'] !== $usrcp->id() || $row['fusername'] !== $usrcp->name()))
                    { // display an msg to say that we are using subscripe system and redirect to subscripes page
                        kleeja_err($olang['KJP_WE_USE_SUBSCRIPE_SYS'], '', true, $config['siteurl'] . 'go.php?go=subscription');
                        $SQL->close();

                        exit;
                    }
                }
                else
                {
                    /**
                     * if the code arrive here , that mean the user have valid subscripe , he is free to do what he want
                     * god bless hem
                     */
                }
            }
            else
            { // the subscription is not active
                if ($row['price'] > 0)
                { // if paid , send hem to buy page
                    // wibsite founders and file Owner can download without pay
                    if ($usrcp->get_data('founder')['founder'] == 0 &&
                         ($row['fuserid'] !== $usrcp->id() || $row['fusername'] !== $usrcp->name()))
                    {
                        redirect($config['siteurl'] . 'do.php?file=' . $row['id']);
                        $SQL->close();

                        exit;
                    }
                } // subscription is not active and the file is free => do nothing , it's not our job
            }
        } // file not found -> kleeja will display an error msg
    } ,

    'err_navig_download_page' => function($args) {
        global $config, $SQL, $dbprefix, $olang, $lang , $tpl , $THIS_STYLE_PATH_ABS , $usrcp, $subscription;

        if (ig('file') && (int) g('file'))
        {
            // if we are using subscription system, and the user trying to buy a file, redirect hem to normal download page
            if ($config['kjp_active_subscriptions'] && $subscription->is_valid($usrcp->id()))
            {
                redirect($config['siteurl'] . 'do.php?id=' . g('file'));

                exit;
            }

            // avilable Payment methods

            $payment_methods = [];

            foreach (getPaymentMethods() as $value)
            {
                $value = trim($value);
                $payment_methods[$value] = ['name' => $olang['KJP_MTHD_NAME_' . strtoupper($value)] , 'method' => $value];
            }

            if (ip('buy_file'))
            {
                if (empty(p('method')) || empty(g('file')))
                {
                    kleeja_err($lang['ERROR_NAVIGATATION']);
                }
                else
                {
                    redirect(KJP::getPayURL('buy_file', (string) p('method'), (int) g('file')));
                }
                
                exit();
            }

            require_once dirname(__FILE__) . '/php/down_ui.php';

            // add Vars to $GLOBAL
            $error = false;
            $tpl->assign('id', $id);
            $tpl->assign('name', $name);
            $tpl->assign('real_filename', $real_filename);
            $tpl->assign('type', $type);
            $tpl->assign('time', $time);
            $tpl->assign('uploads', $uploads);
            $tpl->assign('price', $price);
            $tpl->assign('fusername', $fusername);
            $tpl->assign('REPORT', $REPORT);
            $tpl->assign('userfolder', $userfolder);
            $tpl->assign('size', $size);
            $tpl->assign('FormAction', $FormAction);
            $tpl->assign('payment_methods', $payment_methods);

            Saaheader($title);
            echo $tpl->display($sty, $styPath);
            Saafooter();

            return compact('error');

            $SQL->close();
        }
    } ,

    'default_go_page' => function($args) {
        global $lang , $olang , $usrcp , $config , $THIS_STYLE_PATH_ABS ,$subscription;


        // request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=buy_file&id=1
        // action = buy_file OR join_group or check
        // id = the id of file or the id of group

        // checking request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=check&blablabla
        // blablabla = it's optional for you to using sessions or anythink you want , anyway it will be global varibles


        if (ig('go') && g('go') === 'kj_payment' && ig('method') && !empty(g('method')) && ig('action') && ! empty(g('action')))
        {
            require_once dirname(__FILE__) . '/php/kjPayment.php'; // require the payment interface
            $PaymentMethodClass = dirname(__FILE__) . '/method/' . g('method') . '.php'; // default payment method

            if (! file_exists($PaymentMethodClass))
            {
                $is_err = true;
                is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:set_payment_method', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if ($is_err)
                {
                    kleeja_err('The class file of ' . g('method') . ' payment in not found');

                    exit;
                }
            }

            require_once $PaymentMethodClass;


            $PaymentMethod = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

            // to be sure
            if ($PaymentMethod !== 'kjPayMethod_' . g('method'))
            {
                kleeja_err('Its not your method');

                exit;
            }

            // check if the current payment class is implemented our interface or not
            elseif (! (($PAY = new $PaymentMethod) instanceof KJPaymentMethod))
            {
                kleeja_err('<strong>' . $PaymentMethod . '</strong> class is not implementing (KJPaymentMethod) interface');

                exit;
            }

            elseif (! $PAY::permission('createPayment'))
            {
                kleeja_err('This Method Dont support Creating Payments');

                exit;
            }


            $PAY->paymentStart(); // Play some song to enjoy ;

            $PAY->setCurrency(strtoupper($config['kjp_iso_currency_code']));

            switch (g('action')) {
                case 'buy_file':

                    if (! ig('id'))
                    {
                        kleeja_err($lang['ERROR_NAVIGATATION']);

                        exit;
                    }

                    // user can't buy another file before receive the link of first file
                    // if the user do it , he will lost access to first bought file
                    if ($usrcp->kleeja_get_cookie('mailForDownFile'))
                    {
                        // the user didn't download the file , becuse he did n't set his e-mail
                        redirect($config['siteurl'] . 'go.php?go=KJPaymentMailer');

                        exit;
                    }

                    $fileInfo = getFileInfo(g('id')); // get file information

                    if ($fileInfo['price'] <= 0)
                    {
                        kleeja_err(' The File Is For Free ');

                        exit;
                    }


                    $PAY->CreatePayment('buy_file', $fileInfo);

                    // get some vars for kleeja # compact(':)')
                    foreach ($PAY->varsForCreatePayment() as $varName => $varValue)
                    {
                        $GLOBALS[$varName] = $varValue;
                    }


                    break;

                case 'join_group':
                    // Joining Group Steps
                    if (! ig('id'))
                    {
                        kleeja_err($lang['ERROR_NAVIGATATION']);

                        exit;
                    }

                    $userIs = $usrcp->get_data('group_id');

                    if (! $usrcp->name())
                    {  // the Guests have to signup befor join ..
                        /** $usrcp->id() == false && !$usrcp->id() this options does not work here */
                        kleeja_err($lang['USER_PLACE']);

                        exit;
                    }
                    elseif ($userIs['group_id'] == g('id'))
                    { // if the user is in this group .. note : $usrcp->group_id() also was making problems here , the user need to lougout
                        kleeja_err($olang['KJP_CNT_JOIN']);

                        exit;
                    }
                    elseif ($userIs['group_id'] == 1 && ! defined('DEV_STAGE'))
                    {
                        kleeja_err('YOU ARE ADMIN');

                        exit;
                    }
                    else
                    {
                        $groupInfo = getGroupInfo($args['d_groups'], g('id'));

                        if ($groupInfo)
                        {
                            $PAY->CreatePayment('join_group', $groupInfo);

                            foreach ($PAY->varsForCreatePayment() as $varName => $varValue)
                            {
                                $GLOBALS[$varName] = $varValue;
                            }
                        }
                        else
                        {
                            kleeja_err("It's not allowed to you to join this group");

                            exit;
                        }
                    }

                    break;

                case 'subscripe':

                if (! ig('id'))
                {
                    kleeja_err($lang['ERROR_NAVIGATATION']);

                    exit;
                }

                if (! $usrcp->name())
                {
                    kleeja_err($lang['USER_PLACE'], '', true, $config['siteurl'] . 'go.php?go=subscription');

                    exit;
                }
                elseif ($usrcp->group_id() == 1 && ! defined('DEV_STAGE'))
                {
                    kleeja_err('YOU ARE ADMIN');

                    exit;
                }
                elseif ($subscription->is_valid($usrcp->id()))
                {
                    kleeja_err($olang['KJP_U_H_VALID_SUBSCRIPE']);

                    exit;
                }


                $subscripe_info = $subscription->get(g('id'));

                if (! $subscripe_info)
                {
                    kleeja_err($lang['USER_PLACE'], '', true, $config['siteurl'] . 'go.php?go=subscription');

                    exit;
                }

                $PAY->CreatePayment('subscripe', $subscripe_info);

                foreach ($PAY->varsForCreatePayment() as $varName => $varValue)
                {
                    $GLOBALS[$varName] = $varValue;
                }


                    break;

                case 'check':
                    // Checking Payments steps

                    // i don't want the user reset the cookie expire date
                    if ($usrcp->kleeja_get_cookie('mailForDownFile'))
                    {
                        // the user didn't download the file , becuse he did n't set his e-mail
                        redirect($config['siteurl'] . 'go.php?go=KJPaymentMailer');

                        exit;
                    }

                    $PAY->checkPayment();

                    if ($PAY->isSuccess())
                    {
                        $GLOBALS['title'] = 'successful Payment ' . $_SESSION['kj_payment']['db_id'];
                        $GLOBALS['no_request'] = false;
                        $GLOBALS['stylee'] = 'pay_success';
                        $GLOBALS['FormAction'] = $config['siteurl'] . 'go.php?go=KJPaymentMailer';
                        // to allow the developers to including 'pay_success.html' with their styles .
                        $GLOBALS['styleePath'] = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/paypal.pay_success.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/html/';


                        $global_vars = $PAY->getGlobalVars(); // compact(':)')

                        foreach ($global_vars as $varName => $varValue)
                        {
                            $GLOBALS[$varName] = $varValue;
                        }

                        if ($_SESSION['kj_payment']['payment_action'] == 'buy_file')
                        { // we send e-mail only when the user buying files , no e-mail for joining group
                            // "expected_err"
                            if (! $usrcp->name() || ! user_can('access_bought_files'))
                            { // the user can find the file on bought files , don't need to send the download link
                                if ($PAY->linkMailer())
                                { // if the method support email
                                    $mailTemplate = str_replace(['@fileName' , '@downLink' , '@linkExpire'], [$global_vars['file_name'] , $global_vars['down_link'] , date('Y-m-d / H:i:s', ($config['kjp_down_link_expire'] * 86400) + time())], $GLOBALS['olang']['KJP_MAIL_TPL']); // error here

                                    $mailer =  send_mail($PAY->linkMailer(), $mailTemplate, 'kleeja Payment Download Link', $config['sitemail'], $config['sitename']);

                                    if ($mailer)
                                    { // mail is sent , don't need mail form & dispaly success msg
                                        $GLOBALS['olang']['KJP_DOWN_INFO_2'] = str_replace(['@mail' , '@time'], [$PAY->linkMailer() , date('Y-m-d / H:i:s', ($config['kjp_down_link_expire'] * 86400) + time())], $GLOBALS['olang']['KJP_DOWN_INFO_2']);
                                        $GLOBALS['showMailForm'] = false;
                                        $usrcp->kleeja_set_cookie('downloadFile_' . $_SESSION['kj_payment']['item_id'], $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'], ($config['kjp_down_link_expire'] * 86400) + time());
                                    }
                                    else
                                    { // we have to send mail again , i hope we never never arrive to this part :(
                                        $GLOBALS['showMailForm'] = true;
                                        $GLOBALS['olang']['KJP_DOWN_INFO_2'] = ''; // dont show this msg , we didn't send it yet
                                        $usrcp->kleeja_set_cookie('mailForDownFile', $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'], time() + 86400);
                                    }
                                }
                                else
                                { // method don't support email -> display email form & hide msg & set coockie to use mailform page
                                    $GLOBALS['showMailForm'] = true;
                                    $GLOBALS['olang']['KJP_DOWN_INFO_2'] = ''; // dont show this msg , we didn't send it yet
                                    $usrcp->kleeja_set_cookie('mailForDownFile', $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'], time() + 86400);
                                }
                            }
                            else
                            {
                                $GLOBALS['olang']['KJP_DOWN_INFO_2'] = 'you can see the file and all bought files on <a href="./ucp.php?go=bought_files">Bought Files </a> Page';
                                $GLOBALS['showMailForm'] = false;
                                $usrcp->kleeja_set_cookie('downloadFile_' . $_SESSION['kj_payment']['item_id'], $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'], ($config['kjp_down_link_expire'] * 86400) + time());
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

                if (! $request)
                {
                    kleeja_err('Why i am here ??');
                }

                    break;
            }
        }
        elseif (g('go') == 'paid_group')
        {
            if (ip('join_grp'))
            {
                // to be sure that no one playing with html file
                if (in_array(p('method'), getPaymentMethods()))
                {
                    redirect(KJP::getPayURL('join_group', (string) p('method'), (int) g('group_id')));
                    exit();
                }
            }


            $MethodOption = '';

            foreach (getPaymentMethods() as $value)
            {
                $value = trim($value);

                $MethodOption .= "<option value='{$value}'>" . $olang['KJP_MTHD_NAME_' . strtoupper($value)] . "</option>\n";
                // loop inside loop doesn't work in kleeja styles
            }


            $no_request = false;
            $stylee = 'paid_group';
            $titlee = $olang['KJP_PID_GRP'];
            // to allow the developers to including 'paid_group.html' with their styles .
            $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/paid_group.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/html/';

            $PaidGroups = getGroupInfo($args['d_groups']);

            return compact('no_request', 'titlee', 'stylee', 'styleePath', 'PaidGroups', 'MethodOption');
        }

        // Send Download Link
        elseif (g('go') == 'KJPaymentMailer')
        {
            $payCookieInfo  = $usrcp->kleeja_get_cookie('mailForDownFile');

            if (! $payCookieInfo)
            {
                // ! from check payment page or the mail is sent
                kleeja_err($lang['ERROR_NAVIGATATION']);

                exit;
            }

            $payCookieInfoExplode = explode('_', $payCookieInfo);

            $fileName = getFileInfo($payCookieInfoExplode[0])['name'];


            if (ip('sendMail'))
            {
                $mailAdress = p('buyerMail');

                if (! filter_var($mailAdress, FILTER_VALIDATE_EMAIL))
                {
                    kleeja_err($lang['WRONG_EMAIL'], '', true, $config['siteurl'] . 'go.php?go=KJPaymentMailer', 2);

                    exit; // again :)
                }

                $downloadLink = $config['siteurl'] . 'do.php?downPaidFile=' . $payCookieInfoExplode[0] . '_' . $payCookieInfoExplode[1] . '_' . $payCookieInfoExplode[2];

                $mailTemplate = str_replace(['@fileName' , '@downLink' , '@linkExpire'], [$fileName , $downloadLink , date('Y-m-d / H:i:s', ($config['kjp_down_link_expire'] * 86400) + time())], $GLOBALS['olang']['KJP_MAIL_TPL']);

                $mailer = send_mail($mailAdress, $mailTemplate, 'kleeja Payment Download Link', $config['sitemail'], $config['sitename']);

                if (! $mailer)
                {
                    kleeja_err($olang['KJP_ERR_SND_MIL'], '', true, $config['siteurl'] . 'go.php?go=KJPaymentMailer', 3);

                    exit;
                }
                else
                {

                    // set cookie for download file
                    $usrcp->kleeja_set_cookie('downloadFile_' . $payCookieInfoExplode[0], $payCookieInfo, ($config['kjp_down_link_expire'] * 86400) + time());


                    // delete cookie
                    $usrcp->kleeja_set_cookie('mailForDownFile', 'Finaly done , :)', time() - 86400); // *_*

                    // dispaly success msg || I HOPE WE DONE
                    kleeja_info(
                        str_replace(['@mail' , '@time'], [$mailAdress , date('Y-m-d / H:i:s', ($config['kjp_down_link_expire'] * 86400) + time())], $GLOBALS['olang']['KJP_DOWN_INFO_2'])
                    );
                }
            }

            $titlee = 'Download Mail Sender';
            $no_request = false;
            $FormAction = $config['siteurl'] . 'go.php?go=KJPaymentMailer';
            $stylee = 'kjpayment_mailer';
            $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/kjpaymentmailer.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/html/';
            return compact('stylee', 'styleePath', 'fileName', 'no_request');
        }

        // Subscription list
        // the page to buy a subscripe
        elseif (g('go') == 'subscription')
        {
            // if submit
            if (ip('subscripe_now') && ip('subscripe_id') && ip('Pay_method') && kleeja_check_form_key('subscription'))
            {
                if (! $usrcp->name())
                {
                    kleeja_err($lang['USER_PLACE'], '', true, $config['siteurl'] . 'go.php?go=subscription');

                    exit;
                }
                redirect($config['siteurl'] . 'go.php?go=kj_payment&method=' . p('Pay_method') . '&action=subscripe&id=' . p('subscripe_id'));

                exit;
            }
            $titlee = $olang['KJP_SUBSCRIPTIONS'];
            $no_request = false;
            $FormAction = $config['siteurl'] . 'go.php?go=subscription';
            $stylee = 'subscripe';
            $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/subscripe.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/html/';
            $subscripe_list = $subscription->get();
            $MethodOption = '';
            $form_key = kleeja_add_form_key('subscription');

            foreach (getPaymentMethods() as $value)
            {
                $value = trim($value);

                $MethodOption .= "<option value='{$value}'>" . $olang['KJP_MTHD_NAME_' . strtoupper($value)] . "</option>\n";
                // loop inside loop doesn't work in kleeja styles
            }
            return compact('stylee', 'styleePath', 'fileName', 'no_request', 'subscripe_list', 'MethodOption', 'form_key');
        }
    } ,

    'qr_down_go_page_filename' => function ($args) {
        global $SQL , $usrcp , $config , $subscription , $olang;

        $query = $args['query'];
        $query['SELECT'] .= ', f.price , f.user';
        $result = $SQL->build($query);

        if ($SQL->num_rows($result) > 0)
        {
            $row = $SQL->fetch_array($result);

            // i hate this part
            $redirect = true;

            if ($row['price'] > 0)
            {

                // wibsite founders and file Owner can download without pay
                if ($usrcp->get_data('founder')['founder'] == 0 && ! ($row['user'] === $usrcp->id()))
                {
                    if ($config['kjp_active_subscriptions'] && $subscription->is_valid($usrcp->id()))
                    {// subscriptions is active and the user have a valid subscription

                        $redirect = false;
                        // add a uniq point to file owner
                        $subscription->addPoint($row['id']);
                    }
                    else
                    { // subscriptions is not active , , let's check if he bought the file or not
                        if (ig('downToken') && ig('db'))
                        {
                            $paymentInfo = getPaymentInfo(g('db'), 'item_id = "' . $row['id'] . '" AND payment_action = "buy_file" AND payment_state = "approved" AND payment_token = "' . g('downToken') . '"');


                            if ($paymentInfo)
                            {
                                if ($config['kjp_down_link_expire'] > 0)
                                { // if 0 -> download link will never expire
                                    $downCookie = $usrcp->kleeja_get_cookie('downloadFile_' . g('down'));

                                    if ($downCookie)
                                    {
                                        $downCookie = explode('_', $downCookie);

                                        if (g('down') == $downCookie[0] && g('db') == $downCookie[1] && g('downToken') == $downCookie[2])
                                        {
                                            $month = $paymentInfo['payment_month'];
                                            $day = $paymentInfo['payment_day'];
                                            $year = $paymentInfo['payment_year'];
                                            $payment_time = explode(':', $paymentInfo['payment_time']);
                                            $hour = $payment_time[0];
                                            $minute = $payment_time[1];
                                            $seconde = $payment_time[2];
                                            $paymentTime = mktime($hour, $minute, $seconde, $month, $day, $year);

                                            if ((($config['kjp_down_link_expire'] * 86400) + $paymentTime) >= time())
                                            {
                                                $redirect = false;
                                            }
                                        }
                                    }
                                }
                                else
                                { // $config['down_link_expire'] == 0 -> download link will never expire
                                    $redirect = false;
                                }
                            }
                        }
                    }
                }
                else
                { // the user is founder or file owner
                    $redirect = false;
                }
            }
            elseif ($row['price'] == 0)
            { // the file is free
                if ($config['kjp_active_subscriptions'])
                {// subscripe is active
                    // not Founder or file owner
                    if ($usrcp->get_data('founder')['founder'] == 0 && ! ($row['user'] === $usrcp->id()))
                    {
                        if ($subscription->is_valid($usrcp->id()))
                        { // if he have a valid subscripe
                            $redirect = false;
                        }
                    }
                    else
                    { // the user is founder or file owner
                        $redirect = false;
                    }
                }
                else
                {
                    $redirect = false;
                }// the subscription is not active and free file
            }

            if ($redirect)
            {
                redirect($config['siteurl'] . 'do.php?file=' . $row['id']);
                $SQL->close();

                exit;
            }
        }
    } ,

    'begin_admin_page' => function ($args) {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];
        $adm_extensions[] = 'kj_payment_options';
        $ext_icons['kj_payment_options'] = 'money';
        return compact('adm_extensions', 'ext_icons');
    } ,

    'not_exists_kj_payment_options' => function ($args) {
        $include_alternative = dirname(__FILE__) . '/php/kj_payment_options.php';
        return compact('include_alternative');
    } ,

    'Saaheader_links_func' => function ($args) {
        global $d_groups , $config , $olang , $usrcp , $subscription;
        $top_menu = $args['top_menu'];
        $side_menu = $args['side_menu'];
        $user_is = $args['user_is'];
        // if subscription is active , add user package next to hes name in the header
        $username = $config['kjp_active_subscriptions'] && $subscription->is_valid($usrcp->id()) 
        ? $args['username'] . ' | ' . $subscription->user_subscripe($usrcp->id())['name'] : $args['username'];

        $side_menu[] = ['name' => 'my_kj_payment', 'title' => $olang['R_KJ_PAYMENT_OPTIONS'], 'url' => $config['siteurl'] . 'ucp.php?go=my_kj_payment', 'show' => ($user_is && user_can('recaive_profits') ? true : false)];
        $side_menu[] = ['name' => 'my_payments', 'title' => $args['olang']['KJP_MY_PAYS'], 'url' => $config['siteurl'] . 'ucp.php?go=my_payments', 'show' => ($user_is ? true : false)];
        $side_menu[] = ['name' => 'bought_files', 'title' => $args['olang']['KJP_BOUGHT_FILES'], 'url' => $config['siteurl'] . 'ucp.php?go=bought_files', 'show' => ($user_is && user_can('access_bought_files') ? true : false)];
        $top_menu[] = ['name' => 'paid_group', 'title' => $args['olang']['KJP_PID_GRP'], 'url' => 'go.php?go=paid_group', 'show' => getGroupInfo($d_groups)];
        $top_menu[] = ['name' => 'subscription', 'title' => $args['olang']['KJP_SUBSCRIPTIONS'], 'url' => 'go.php?go=subscription', 'show' => ($config['kjp_active_subscriptions'] && $subscription->get())];

        // i want to put logout to the end if menu always
        if ($user_is)
        {
            $side_menu['logout'] = $side_menu[3];
            unset($side_menu[3]);
        }

        return compact('top_menu', 'side_menu', 'username');
    } ,


    'begin_download_page'  => function ($args) {
        global $config , $usrcp;

        if (ig('downPaidFile'))
        {
            // the mailed link to Buyer mail
            // EX: domain.io/kleeja/go.php?downPaidFile=fileID_dbID_payToken
            $downToken = explode('_', g('downPaidFile'));
            $fileID    = $downToken[0];
            $dbID      = $downToken[1];
            $payToken  = $downToken[2];

            $paymentInfo = getPaymentInfo($dbID, "item_id = '{$fileID}' AND payment_token = '{$payToken}' AND payment_state = 'approved' AND payment_action = 'buy_file'");

            if ($paymentInfo)
            {
                // for this session i made this page
                $_SESSION['HTTP_REFERER'] =  $fileID;

                redirect($config['siteurl'] . 'do.php?down=' . $fileID . '&amp;db=' . $dbID . '&amp;downToken=' . $payToken);

                exit;
            }
            else
            {
                redirect($config['siteurl']); //OR kleeja_err();
            }

            exit;
        }
    },
    'default_usrcp_page' => function ($args) {
        global $SQL , $dbprefix , $usrcp , $config ,$olang , $userinfo ,$d_groups, $THIS_STYLE_PATH_ABS;
        // all user bought file
        if (g('go') == 'bought_files')
        {
            if (! $usrcp->name() || ! user_can('access_bought_files')):
                return;
            endif; // the page is for members only

            $user_is = $usrcp->id();

            $titlee       = $olang['KJP_BOUGHT_FILES'];
            $no_request   = false;
            $stylee       = 'bought_files';
            $styleePath   = $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/bought_files.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';
            $havePayments = false;

            $query = [
                'SELECT'   => 'id , payment_token , item_name, item_id , payment_currency , payment_amount, payment_year , payment_month , payment_day , payment_time' ,
                'FROM'     => $dbprefix . 'payments' ,
                'WHERE'    => "payment_state = 'approved' AND user = {$user_is} AND payment_action = 'buy_file'" ,
                'ORDER BY' => 'id DESC'
            ];

            $all_payments = $SQL->build($query);

            if ($num_rows = $SQL->num_rows($all_payments))
            {
                $perpage          = 21;
                $currentPage    = ig('page') ? g('page', 'int') : 1;
                $Pager            = new Pagination($perpage, $num_rows, $currentPage);
                $start            = $Pager->getStartRow();
                $linkgoto       = $config['siteurl'] . 'ucp.php?go=bought_files';
                $page_nums        = $Pager->print_nums($linkgoto);
                $query['LIMIT'] = "$start, $perpage";
                $all_payments = $SQL->build($query);


                $myPayments = [];
                $havePayments = true;

                while ($pay = $SQL->fetch($all_payments))
                {
                    $myPayments[] = [
                        'ID'        => $pay['id'] ,
                        'FILE'      => $pay['item_name'] ,
                        'AMOUNT'    => $pay['payment_amount'] . ' ' . $pay['payment_currency'],
                        'DATE_TIME' => $pay['payment_day'] . '-' . $pay['payment_month'] . '-' . $pay['payment_year'] . ' / ' . $pay['payment_time'] ,
                        'DOWN_LINK' => $config['siteurl'] . 'do.php?downPaidFile=' . $pay['item_id'] . '_' . $pay['id'] . '_' . $pay['payment_token']
                    ];
                }
            }



            return compact('titlee', 'no_request', 'stylee', 'page_nums', 'styleePath', 'myPayments', 'havePayments');
        }
        elseif (g('go') == 'my_payments')
        {
            $titlee       = $olang['KJP_MY_PAYS'];
            $no_request   = false;
            $stylee       = 'my_payments';
            $styleePath   = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/my_payments.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';


            $myPaymentQuery = [
                'SELECT'    => 'p.id , p.payment_method , p.payment_amount , p.payment_action , p.item_name , p.payment_year , p.payment_month , p.payment_day , p.payment_time',
                'FROM'      => "{$dbprefix}payments p",
                'WHERE'     => 'p.user = ' . $usrcp->id(),
                'ORDER BY'  => 'p.id DESC',
            ];

            $myPays = $SQL->build($myPaymentQuery);

            $havePayments = false;

            if ($num_rows = $SQL->num_rows($myPays))
            {
                $perpage          = 21;
                $currentPage    = ig('page') ? g('page', 'int') : 1;
                $Pager            = new Pagination($perpage, $num_rows, $currentPage);
                $start            = $Pager->getStartRow();
                $linkgoto       = $config['siteurl'] . 'ucp.php?go=my_payments';
                $page_nums        = $Pager->print_nums($linkgoto);
                $myPaymentQuery['LIMIT'] = "$start, $perpage";
                $myPays = $SQL->build($myPaymentQuery);

                $UserById = UserById();


                $payments = [];
                $havePayments = true;
                while ($row = $SQL->fetch_array($myPays))
                {
                    $payments[] = [
                        'ID'         => $row['id'],
                        'METHOD'     => $olang['KJP_MTHD_NAME_' . strtoupper($row['payment_method'])],
                        'FILE_NAME'  => $row['item_name'],
                        'AMOUNT'     => $row['payment_amount'] . ' ' . $config['kjp_iso_currency_code'],
                        'ACTION'     => sprintf($olang['KJP_ACT_' . strtoupper($row['payment_action'])], $row['item_name']),
                        'DATE_TIME'  => "{$row['payment_year']}-{$row['payment_month']}-{$row['payment_day']} / {$row['payment_time']}",
                    ];
                }
            }
            return compact('titlee', 'stylee', 'styleePath', 'page_nums', 'payments', 'havePayments', 'no_request');
        }
        // Payment UCP
        elseif (g('go') == 'my_kj_payment')
        {
            if (! user_can('recaive_profits'))
            {
                // this is not Guests page
                return;
            }
            $action           = $config['siteurl'] . 'ucp.php?go=my_kj_payment'; // for withdraw form
            $case             = ig('case') ? g('case') : 'cp';
            $userData         = $usrcp->get_data('subs_point ,balance , password_salt');
            $user_balance     = $userData['balance'] . ' ' . strtoupper($config['kjp_iso_currency_code']); // to have it fresh
            $user_subs_points = $userData['subs_point'];
            $username         = $usrcp->name();
            $user_id          = $usrcp->id();
            $titlee           = 'KJ Payment CP';
            $no_request       = false;
            $stylee           = 'my_kj_payment';
            $styleePath       = $styleePath = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/my_kj_payment.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/html/';

            // request your money
            if (ip('requestAmount'))
            {
                require_once dirname(__FILE__) . '/php/kjPayment.php'; // require the payment interface
                $PaymentMethodClass = dirname(__FILE__) . '/method/' . p('PayoutMethod') . '.php'; // default payment method

                if (! file_exists($PaymentMethodClass))
                {
                    is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:createPayout', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                    if (! file_exists($PaymentMethodClass))
                    {
                        kleeja_admin_err('The class file of ' . p('PayoutMethod') . ' payment is not found');

                        exit;
                    }
                }
                require_once $PaymentMethodClass;

                $methodClassName = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

                if (! $methodClassName::permission('createPayout'))
                {
                    kleeja_err('The method dont support Creating Payouts');

                    exit;
                }
                // if erro password stop
                if (empty(p('userPass')) || ! $usrcp->kleeja_hash_password(p('userPass') . $userData['password_salt'], $userinfo['password']))
                {
                    kleeja_err('your password is not correct');

                    exit;
                }
                elseif (($lmt = getGroupInfo($d_groups, $usrcp->group_id())['kjp_min_payout_limit']) !== '0' && $lmt > p('AmountNumber'))
                {
                    kleeja_err(sprintf($olang['KJP_MIN_POUT_LMT'], $lmt, $config['kjp_iso_currency_code']));

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
                        kleeja_err($olang['KJP_NOT_VAILED_AMNT']);

                        exit;
                    }
                    else
                    {
                        if (! (float) $requestAmount)
                        {
                            kleeja_err($olang['KJP_NOT_VAILED_AMNT']);

                            exit;
                        }

                        $method   = 'paypal'; // until now , only method support payout is paypal
                        $amount   = $requestAmount;
                        $state    = 'verify';
                        $payout_year      = date('Y');
                        $payout_month     = date('m');
                        $payout_day       = date('d');
                        $payout_time      = date('H:i:s');
                        $payment_more_info = payment_more_info('to_db', [
                            'SENDTO' => $usrcp->mail(),
                        ]);

                        $query = [
                            'INSERT'       => 'user , payment_more_info , method , amount , state , payout_year , payout_month , payout_day , payout_time ',
                            'INTO'         => "{$dbprefix}payments_out",
                            'VALUES'       => "'{$user_id}' , '{$payment_more_info}' , '{$method}' , '{$amount}' , '{$state}' , '{$payout_year}' , '{$payout_month}' , '{$payout_day}' , '{$payout_time}'"
                        ];

                        $SQL->build($query);

                        if ($SQL->affected())
                        {
                            $new_balance = $user_balance - $requestAmount;
                            $SQL->query("UPDATE {$dbprefix}users SET `balance` = '{$new_balance}' WHERE `name` = '{$username}'");
                            kleeja_info(sprintf($olang['KJP_SUCES_SND_WTHD'], $requestAmount, $new_balance));
                        }
                    }
                }
            }

            if ($case == 'withdrawals')
            {
                // only if the case is (Requested_Amounts) we will call this query
                $query = [
                    'SELECT'   => '*' ,
                    'FROM'     => "{$dbprefix}payments_out",
                    'WHERE'    => "`user` = '{$user_id}'",
                    'ORDER BY' => 'id DESC'
                ];

                $result = $SQL->build($query);
                $havePayout = false;

                if ($num_rows = $SQL->num_rows($result))
                {
                    $perpage          = 21;
                    $currentPage    = ig('page') ? g('page', 'int') : 1;
                    $Pager            = new Pagination($perpage, $num_rows, $currentPage);
                    $start            = $Pager->getStartRow();
                    $linkgoto       = $config['siteurl'] . 'ucp.php?go=my_kj_payment&case=withdrawals';
                    $page_nums        = $Pager->print_nums($linkgoto);
                    $query['LIMIT'] = "$start, $perpage";
                    $result = $SQL->build($query);


                    $payouts = [];
                    $havePayout = true;
                    while ($row = $SQL->fetch_array($result))
                    {
                        $payouts[] = [
                            'ID'           => $row['id'],
                            'METHOD'       => $row['method'],
                            'AMOUNT'       => $row['amount'] . ' ' . $config['kjp_iso_currency_code'],
                            'DATE_TIME'    => "{$row['payout_year']}-{$row['payout_month']}-{$row['payout_day']} / {$row['payout_time']}",
                            'STATE_LANG'   => $olang['KJP_POUT_ST_' . strtoupper($row['state'])] ?? $row['state'],
                            'STATE'        => $row['state']
                        ];
                    }
                }
            }
            elseif ($case == 'files_payments')
            {
                $fileQuery = [
                    'SELECT'  => 'p.id , p.payment_method , p.item_name , p.item_id , p.user , p.payment_year , p.payment_month , p.payment_day , p.payment_time',
                    'FROM'    => "{$dbprefix}payments p",
                    'JOINS'   =>
                    [
                        [
                            'INNER JOIN' => "{$dbprefix}files f",
                            'ON'         => 'p.item_id = f.id'
                        ]
                    ],
                    'WHERE'     => 'f.user = ' . $usrcp->id() . " AND p.payment_action = 'buy_file'",
                    'ORDER BY'  => 'p.id DESC',
                ];

                $filePay = $SQL->build($fileQuery);

                $havePayments = false;

                if ($num_rows = $SQL->num_rows($filePay))
                {
                    $perpage          = 21;
                    $currentPage      = ig('page') ? g('page', 'int') : 1;
                    $Pager            = new Pagination($perpage, $num_rows, $currentPage);
                    $start            = $Pager->getStartRow();
                    $linkgoto         = $config['siteurl'] . 'ucp.php?go=my_kj_payment&case=files_payments';
                    $page_nums        = $Pager->print_nums($linkgoto);
                    $fileQuery['LIMIT'] = "$start, $perpage";
                    $filePay = $SQL->build($fileQuery);

                    $UserById = UserById();


                    $payments = [];
                    $havePayments = true;
                    while ($row = $SQL->fetch_array($filePay))
                    {
                        $payments[] = [
                            'ID'        => $row['id'],
                            'METHOD'    => $olang['KJP_MTHD_NAME_' . strtoupper($row['payment_method'])],
                            'FILE_NAME' => '<a href="' . $config['siteurl'] . 'do.php?id=' . $row['item_id'] . '" target="_blank">' . $row['item_name'] . '</a>',
                            'BUYER'     => ! empty($UserById[$row['user']]) ? $UserById[$row['user']] : 'Guest',
                            'DATE_TIME' => "{$row['payment_year']}-{$row['payment_month']}-{$row['payment_day']} / {$row['payment_time']}",
                        ];
                    }
                }
            }
            elseif ($case == 'pricing_file')
            {
                if (ip('open_file'))
                {
                    $select_file_id =  ip('select_file_id') ? p('select_file_id') : null;

                    $ExampleID = $config['siteurl'] . 'do.php?id=';
                    $ExampleIMG = $config['siteurl'] . 'do.php?img=';

                    if (! (int) $select_file_id)
                    {
                        $select_file_id = str_replace([$ExampleID , $ExampleIMG], '', $select_file_id);
                    }

                    if ($select_file_id !== null && $select_file_id > 0 && $file_info = getFileInfo($select_file_id))
                    {
                        if ($file_info['user'] == $usrcp->id())
                        { // to be sure that every user will change hes files only
                            $show_price_panel = true;
                            $FileID = $file_info['id'];
                            $FileName = $file_info['name'];
                            $FileSize = readable_size($file_info['size']);
                            $FileUser = $usrcp->name();
                            $FilePrice = $file_info['price'];
                        }
                        else
                        {
                            $OpenAlert = true;
                            $AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] . ' ' . $select_file_id;
                            $AlertRole = 'danger';
                        }
                    }
                }
                elseif (ip('set_price'))
                {
                    $FileID = (int) p('price_file_id');
                    $FileName = p('file_name');
                    $FilePrice = p('price_file');

                    if ($FilePrice < $config['kjp_min_price_limit'] || $FilePrice > $config['kjp_max_price_limit'])
                    {
                        kleeja_err(sprintf($olang['KJP_PRC_LMT'], $config['kjp_min_price_limit'], $config['kjp_max_price_limit'], $config['kjp_iso_currency_code']));

                        exit;
                    }

                    if ($file_info = getFileInfo($FileID, 'user = ' . $usrcp->id()))
                    {
                        $update_query = [
                            'UPDATE' => $dbprefix . 'files' ,
                            'SET'    => "price = '{$FilePrice}'" ,
                            'WHERE'  => "id = '{$FileID}' AND real_filename = '{$FileName}' AND user = " . $usrcp->id()
                        ];

                        $SQL->build($update_query);

                        if ($SQL->affected())
                        {
                            $OpenAlert = true;
                            $AlertMsg = sprintf($olang['KJP_NO_FILE_NEW_PRICE'], $FileName, $FilePrice, strtoupper($config['kjp_iso_currency_code']));
                            $AlertRole = 'success';
                        }
                    }
                    else
                    {
                        $OpenAlert = true;
                        $AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] . ' ' . $FileID;
                        $AlertRole = 'danger';
                    }
                }
            }
            elseif ($case == 'my_paid_files')
            {
                $all_paid_file = [];

                $query = [
                    'SELECT' => 'id , real_filename , price' ,
                    'FROM'   => "{$dbprefix}files" ,
                    'WHERE'  => 'price > 0 AND user = ' . $usrcp->id()
                ];

                $paid_f = $SQL->build($query);

                $page_nums = $have_paid_file = false;

                if ($num_rows = $SQL->num_rows($paid_f))
                {

                   // Pagination //

                    $perpage                 = 21;
                    $currentPage             = ig('page') ? g('page', 'int') : 1;
                    $Pager                   = new Pagination($perpage, $num_rows, $currentPage);
                    $start                   = $Pager->getStartRow();
                    $linkgoto                = $config['siteurl'] . 'ucp.php?go=my_kj_payment&amp;case=my_paid_files';
                    $page_nums               = $Pager->print_nums($linkgoto);
                    $query['LIMIT']          = "$start, $perpage";
                    $paid_f                  = $SQL->build($query);



                    $have_paid_file = true;
                    while ($paid_file = $SQL->fetch($paid_f))
                    {
                        $all_paid_file[] = [
                            'id'    => $paid_file['id'] ,
                            'name'  => $paid_file['real_filename'] ,
                            'price' => $paid_file['price'] . ' ' . $config['kjp_iso_currency_code'],
                            'link'  => $config['siteurl'] . 'do.php?id=' . $paid_file['id']
                        ];
                    }
                }
            }
            return compact('have_paid_file','all_paid_file', 'user_subs_points',
                'AlertRole','AlertMsg','OpenAlert',
                'show_price_panel','FileID', 'FileName','FileUser','FilePrice','FileSize',
                'havePayments','payments','page_nums','havePayout','payouts',
            'case', 'action', 'titlee', 'no_request', 'stylee', 'styleePath', 'user_balance');
        }
    } ,

    'login_data_no_error' => function ($args) {
        // after login , if the admin change the permission , the user have to login again to take the cookies
        if (user_can('access_bought_files')) :

        // success login , get all payment that user made it , and set the cookie to have access to download page
        global $usrcp , $SQL , $dbprefix;

        // $user_id = $usrcp->id(); doesn't work :( , i don't know why

        $username = $usrcp->name();
        $user_id = $SQL->fetch($SQL->query("SELECT id FROM {$dbprefix}users WHERE `name` LIKE '{$username}'"));
        $user_id = $user_id['id'];

        $query = [
            'SELECT' => 'id , payment_token , item_id',
            'FROM'   => $dbprefix . 'payments' ,
            'WHERE'  => "payment_state = 'approved' AND user = {$user_id} AND payment_action = 'buy_file'" ,
        ];

        $boughtFiles = $SQL->build($query);

        if ($SQL->num_rows($boughtFiles))
        { // if we have payments
            while ($payInfo = $SQL->fetch($boughtFiles))
            {
                $usrcp->kleeja_set_cookie('downloadFile_' . $payInfo['item_id'], $payInfo['item_id'] . '_' . $payInfo['id'] . '_' . $payInfo['payment_token'], time() + (86400 * 31));
            }
        }

        endif;
    } ,

    'begin_logout' => function ($args) {
        // delete the cookies of bought files
        // exacly like we made in login steps , only change the expire data from after 1 month to befor 1 month :)
        global $usrcp , $SQL , $dbprefix;

        $user_id = $usrcp->id();

        $query = [
            'SELECT' => 'id , payment_token , item_id',
            'FROM'   => $dbprefix . 'payments' ,
            'WHERE'  => "payment_state = 'approved' AND user = {$user_id} AND payment_action = 'buy_file'" ,
        ];

        $boughtFiles = $SQL->build($query);

        if ($SQL->num_rows($boughtFiles))
        { // if we have payment
            while ($payInfo = $SQL->fetch($boughtFiles))
            {
                $usrcp->kleeja_set_cookie('downloadFile_' . $payInfo['item_id'], $payInfo['item_id'] . '_' . $payInfo['id'] . '_' . $payInfo['payment_token'], time() - (86400 * 31));
            }
        }
    } ,

    'boot_common' => function ($args) {
        global $olang , $config;
        define('support_kjPay', true);

        // to check if the plugin is installed and enabled
        if (defined('support_kjPay'))
        {
            // a payment without salt please
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
        $subscription = new Subscription;
        $is_style_supported = is_style_supported();

        return compact('olang', 'subscription', 'is_style_supported');
    },
    'go_queue' => function ($args) {
        global $subscription;
        $subscription->convertPoints();
    },
    'KJP:get_payment_methods' => function ($args) {
        global $config;
        $return = $args['return']; // all methods in DB

        /**
         * in the next lines, we will check all methods in db
         * for each method, there is some conditions that have to be applied to use the method
         * and any method that don't apply the condition we will add it to this array, to remove it later in one time
         */
        $filter = [];

        // check if the user can use the Balance method or not
        if (! user_can('recaive_profits') && in_array('balance', $return))
        {
            $filter[] = 'balance';
        }

        // check if PayPal method is ready to use or not
        if (in_array('paypal', $return) &&
         ((empty($config['kjp_paypal_client_id']) || empty($config['kjp_paypal_client_secret'])) ||
         ! file_exists(dirname(__FILE__) . '/vendor/autoload.php'))
         ) {
            $filter[] = 'paypal';
        }

        // check if Stripe method is ready to use or not
        if (in_array('cards', $return) &&
         (empty($config['kjp_stripe_publishable_key']) || empty($config['kjp_stripe_secret_key']) ||
         ! file_exists(dirname(__FILE__) . '/stripe-sdk/vendor/autoload.php'))
         ) {
            $filter[] = 'cards';
        }

        $return = array_filter($return, function ($mthd) use ($filter) {
            if (! in_array($mthd, $filter))
            {
                return $mthd;
            }
        });

        return compact('return');
    },
    'default_admin_page' => function ($args) {
        global $lang, $olang, $config;
        $ADM_NOTIFICATIONS = $args['ADM_NOTIFICATIONS'];

        $payment_methods = getPaymentMethods(true);

        // check if PayPal method is ready to use or not
        // let's check the configs of paypal in DB
        if (in_array('paypal', $payment_methods) && ((empty($config['kjp_paypal_client_id']) || empty($config['kjp_paypal_client_secret'])))
         ) {
            $ADM_NOTIFICATIONS[]  = [
                'id'      => 'EmptyPaypalParms',
                'msg_type'=> 'error',
                'title'   => $olang['KJP_PAYPAL_PRMS_EMPTY_TITLE'],
                'msg'     => $olang['KJP_PAYPAL_PRMS_EMPTY']
            ];
        }

        if (in_array('paypal', $payment_methods) && ! file_exists(dirname(__FILE__) . '/vendor/autoload.php'))
        {
            $ADM_NOTIFICATIONS[]  = [
                'id'      => 'NoPaypalLib',
                'msg_type'=> 'error',
                'title'   => $olang['KJP_PAYPAL_NO_LIB_TITLE'],
                'msg'     => $olang['KJP_PAYPAL_NO_LIB']
            ];
        }

        // check if Stripe method is ready to use or not
        if (in_array('cards', $payment_methods) && (empty($config['kjp_stripe_publishable_key']) || empty($config['kjp_stripe_secret_key'])))
        {
            $ADM_NOTIFICATIONS[]  = [
                'id'      => 'EmptyStripeParms',
                'msg_type'=> 'error',
                'title'   => $olang['KJP_STRIPE_PRMS_EMPTY_TITLE'],
                'msg'     => $olang['KJP_STRIPE_PRMS_EMPTY']
            ];
        }

        if (in_array('cards', $payment_methods) && ! file_exists(dirname(__FILE__) . '/stripe-sdk/vendor/autoload.php'))
        {
            $ADM_NOTIFICATIONS[]  = [
                'id'      => 'NoStripeLib',
                'msg_type'=> 'error',
                'title'   => $olang['KJP_STRIPE_NO_LIB_TITLE'],
                'msg'     => $olang['KJP_STRIPE_NO_LIB']
            ];
        }

        return compact('ADM_NOTIFICATIONS');
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


];


// Plugin Functions;
require_once dirname(__FILE__) . '/php/function.php';
require_once dirname(__FILE__) . '/php/kjp_api.php';
require_once dirname(__FILE__) . '/php/subscription.php';
