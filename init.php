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
    'plugin_kleeja_version_min' => '3.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kleeja_payment']['first_run']['ar'] = "
باستخدام هذا البرنامج المساعد ، يمكنك تسعير الملفات والمجموعات لبيعها ، واستلام الدفعات إلى حساب paypal الخاص بك تلقائيًا <br>
قم يزيارة صفحة الساعدة للمزيد <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >الساعدة</a>

";

$kleeja_plugin['kleeja_payment']['first_run']['en'] = "
With this plugin you can pricing the files and groups to selling it , and recive the payyments to your paypal account automaticly
( WebMasters Only ) <br>

for more info visit help page <br>
<a href='./index.php?cp=kj_payment_options&smt=help' >Help</a>

";

# plugin installation function
$kleeja_plugin['kleeja_payment']['install'] = function ($plg_id) {

    global $SQL , $dbprefix;

    $add_table = "CREATE TABLE IF NOT EXISTS `{$dbprefix}payments` (
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
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
    
    $SQL->query( $add_table );

    $SQL->query("ALTER TABLE `{$dbprefix}files` ADD `price` FLOAT NOT NULL DEFAULT '0';");


    $options = array(
        'join_price' =>
            array(
                'value'  => '0',
                'html'   => configField('join_price'),
                'plg_id' => $plg_id,
                'type'   => 'groups',
                'order'  => '1',
            ),

            'pp_client_id' => // paypal method
            array(
                'value'  => '0',
                'html'   => configField('pp_client_id'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),

            'paypal_client_secret' => #paypal method
            array(
                'value'  => '0',
                'html'   => configField('paypal_client_secret'),
                'plg_id' => $plg_id,
                'type'   => 'kleeja_payment',
            ),
            'iso_currency_code' => // paid membership plugin
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
            )

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


    add_config_r($options);


    // Language .. 
    add_olang(array(
        'R_KJ_PAYMENT_OPTIONS'                => 'مدفوعات كليجا' ,
        'JOIN_PRICE'                          => 'سعر الإنضمام' ,
        'PP_CLIENT_ID'                        => 'معرف العميل ( PayPal Client ID )' ,
        'PAYPAL_CLIENT_SECRET'                => 'كلمة السر لمعرف العميل ( PayPal Secret )' ,
        'ISO_CURRENCY_CODE'                   => 'رمز العملة ISO' ,
        'DOWN_LINK_EXPIRE'                    => 'تنتهي صلاحية رابط التنزيل بعد س يوم ( 0 ) صالح دائما' ,
        'CONFIG_KLJ_MENUS_KLEEJA_PAYMENT'     => 'إعدادات Kleeja Payment' ,
        'KJP_ALL_TRNC'                        => 'جميع المعاملات',
        'KJP_TRNC'                            => 'المعاملات',
        'KJP_NT_PRFIT'                        => 'صافي الربح',
        'KJP_TRNC_CP_INFO'                    => 'هذه هي أرقام جميع المدفوعات (شراء الملفات والانضمام إلى المجموعات).',
        'KJP_D_TRNC'                          => 'المعاملات اليومية',
        'KJP_M_TRNC'                          => 'المعاملات الشهرية',
        'KJP_OPN_ARCHIVE'                     => 'افتح الأرشيف',
        'KJP_ARCHIVE_NOTE'                    => 'اترك اليوم فارغًا إذا كنت تريد أرشيفًا شهريًا',
        'KJP_VIEW_ALL'                        => 'عرض الجميع',
        'KJP_VIEW'                            => 'عرض',
        'KJP_ARCH_OPN'                        => 'افتح الأرشيف',
        'KJP_PAY_OPN'                         => 'افتح دفعة',
        'KJP_PAY_NUM'                         => 'عدد المدفوعات',
        'KJP_NO_PEND_PAY'                     => 'لا يوجد المدفوعات المعلقة ..',
        'KJP_PEND_PAY'                        => 'دفعات قيد التحقق',
        'KJP_MEMBER'                          => 'عضو',
        'KJP_ACTION'                          => 'عملية',
        'KJP_DATE_TIME'                       => 'وقت التاريخ',
        'KJP_PAY_ID'                          => 'رقم عملية الدفع',
        'KJP_VIW_TPL_PAYPAL_PAYMENT_ID'       => '(Paypal)رقم عملية الدفع',
        'KJP_PAY_AMNT'                        => 'مبلغ',
        'KJP_VIW_TPL_PAYPAL_PAYMENT_FEES'     => '(Paypal)الرسوم',
        'KJP_PAY_TKN'                         => 'رمز الدفع',
        'KJP_VIW_TPL_PAYPAL_PAYER_MAIL'       => 'بريد المشتري',
        'KJP_PAY_ITM'                         => 'العنصر',
        'KJP_FILE_PAYMNT'                     => 'كل عمليات الدفع للملف',
        'KJP_GRP_PAYMNT'                      => 'كل دفعات الانضمام للمجموعة',
        'KJP_USR_PAYMNT'                      => 'جميع عمليات الدفع للمستخدم',
        'KJP_IP_PAYMNT'                       => 'جميع عمليات الدفع للزائر',
        'KJP_PRC_FILE'                        => 'تسعير ملف',
        'KJP_PAID_FILE'                       => 'الملفات المدفوعة',
        'KJP_HLP'                             => 'مساعدة',
        'KJP_ENT_ID_URL'                      => 'أدخل معرف الملف أو عنوان URL للملف',
        'KJP_OPN_FILE'                        => 'افتح الملف',
        'KJP_FILE_INFO'                       => 'ملف المعلومات',
        'KJP_FILE_NAME'                       => 'اسم الملف',
        'KJP_FILE_OWNR'                       => 'مالك الملف',
        'KJP_FILE_SZE'                        => 'حجم الملف',
        'KJP_SET_PRC'                         => 'ضع سعر',
        'KJP_PRC'                             => 'السعر',
        'KJP_BUY_FILE'                        => 'شراء الملف',
        'KJP_BUY'                             => 'شراء',
        'KJP_SCES_PAY'                        => 'تمت عملية الدفع بنجاح',
        'KJP_DOWN_INFO_1'                     => 'يمكنك الآن تنزيل الملف بالنقر فوق',
        'KJP_DOWN_INFO_2'                     => 'تم إرسال نسخة من رابط التنزيل إلى البريد الإلكتروني @mail ، وسوف تنتهي صلاحيته في @time',
        'KJP_GRP_NAME'                        => 'أسم المجموعة',
        'KJPP_GRP_JOIN_LNK'                   => 'رابط الإنضمام',
        'KJP_GRP_INFO'                        => 'للتحقق من الامتدادات المسموح بها للمجموعات ، تفضل بزيارة',
        'KJP_NO_FILE_WITH_ID'                 => 'لم يتم العثور على ملف بالمعرف',
        'KJP_NO_FILE_NEW_PRICE'               => 'سعر الملف هو الآن' ,
        'KJP_PAY_ID_FALSE'                    => 'لا يوجد عمليات دفع بهذا المعرّف ..' ,
        'KJP_NO_PAID_FILES'                   => 'لا يوجد ملفات مدفوعة حتى الآن ..' ,
        'KJP_NO_PAY_ARCH'                     => 'لا يوجد مدفوعات من تاريخ محدد ..' ,
        'KJP_FILE_TRNCS'                      => 'معاملات الملفات' ,
        'KJP_GRP_TRNCS'                       => 'معاملات الانضمام إلى الجماعات' ,
        'KJP_ARC_PAYS'                        => 'ارشيف المدفوعات' ,
        'KJP_VIEW_PAY'                        => 'عرض عملية الدفع' ,
        'KJP_PAYR_IP'                         => 'IP الخاص بالمشتري' ,
        'KJP_VIW_TPL_PAYPAL_PAYER_NAME'       => 'اسم المشتري' ,
        'KJP_BYNG_FILE'                       => 'شراء الملف' ,
        'KJP_JUNG_GRP'                        => 'الانضمام إلى المجموعة' ,
        'KJP_JUIN'                            => 'انضم' ,
        'KJP_JUIN_SUCCESS'                    => 'أنت الآن عضو في المجموعة' ,
        'KJP_CNT_JOIN'                        => 'أنت في هذه المجموعة ، لا يمكنك الانضمام مرة أخرى' ,
        'KJP_GUEST'                           => 'زائر' ,
        'KJP_PID_GRP'                         => 'المجموعات المدفوعة' ,
        'KJP_VIW_TPL_PAYPAL_PAYER_ID'         => 'معرف الدافع (PAYPAL )',
        'KJP_PAY_BY_MTHD'                     => 'جميع عمليات الدفع عن طريق' ,
        'KJP_PAY_MTHD'                        => 'طريقة الدفع' ,
        'CONFIG_KLJ_MENUS_KJ_PAY_ACTIVE_MTHD' => 'طرق الدفع النشطة',
        'ACTIVE_PAYPAL'                       => 'تنشط باي بال' ,
        'KJP_MAIL_TPL' => "تم شراء ملف @fileName   بنجاح \r\n يمكنك تنزيل الملف من: @downLink  \r\n ستنتهي صلاحية هذا الرابط على: @linkExpire" ,
        'KJP_MAIL'                            => 'عنوان بريد الكتروني',
        'KJP_MAIL_INFO_1'                     => 'تلقي رابط التحميل عبر البريد الإلكتروني',
        'KJP_MAIL_INFO_2'                     => 'سنرسل رابط التنزيل لهذا البريد الإلكتروني',
        'KJP_CANT_JOIN_GRP'                   => 'غير مسموح لك بالانضمام إلى هذه المجموعة',
    ),
        'ar',
        $plg_id);

    add_olang(array(
        'R_KJ_PAYMENT_OPTIONS'                => 'Kleeja Payment',
        'JOIN_PRICE'                          => 'Join Price',
        'PP_CLIENT_ID'                        => 'PayPal Client ID',
        'PAYPAL_CLIENT_SECRET'                => 'PayPal Client Secret',
        'ISO_CURRENCY_CODE'                   => 'ISO Currency Code',
        'DOWN_LINK_EXPIRE'                    => 'Dowload Link Expire After x Days ( 0 ) avilabe forever' ,
        'CONFIG_KLJ_MENUS_KLEEJA_PAYMENT'     => 'Kleeja Payment Setting',
        'KJP_ALL_TRNC'                        => 'All Transactions',
        'KJP_TRNC'                            => 'Transactions',
        'KJP_NT_PRFIT'                        => 'Net Profit',
        'KJP_TRNC_CP_INFO'                    => 'This is the numbers of all payments ( Buying files and joining the groups ) .',
        'KJP_D_TRNC'                          => 'Daily Transactions',
        'KJP_M_TRNC'                          => 'Monthly Transactions',
        'KJP_OPN_ARCHIVE'                     => 'Open an Archive',
        'KJP_ARCHIVE_NOTE'                    => 'leave the day empty if you want monthly archive',
        'KJP_VIEW_ALL'                        => 'view all',
        'KJP_VIEW'                            => 'VIEW',
        'KJP_ARCH_OPN'                        => 'OpenArchive',
        'KJP_PAY_OPN'                         => 'Open a Payment',
        'KJP_PAY_NUM'                         => 'Payment Number',
        'KJP_NO_PEND_PAY'                     => 'There is no Pending Payments ..',
        'KJP_PEND_PAY'                        => 'Pending Payments',
        'KJP_MEMBER'                          => 'MEMBER',
        'KJP_ACTION'                          => 'ACTION',
        'KJP_DATE_TIME'                       => 'DATE/TIME',
        'KJP_PAY_ID'                          => 'Payment ID',
        'KJP_PAY_AMNT'                        => 'Payment Amount',
        'KJP_PAY_TKN'                         => 'Payment Token',
        'KJP_PAY_ITM'                         => 'Item',
        'KJP_FILE_PAYMNT'                     => 'All payment of file ',
        'KJP_GRP_PAYMNT'                      => 'All payment of joining group ',
        'KJP_USR_PAYMNT'                      => 'All payment of user  ',
        'KJP_IP_PAYMNT'                       => 'All payment of ip  ',
        'KJP_PRC_FILE'                        => 'Pricing a File',
        'KJP_PAID_FILE'                       => 'Paid Files',
        'KJP_HLP'                             => 'Help',
        'KJP_ENT_ID_URL'                      => 'Enter the File ID or File URL',
        'KJP_OPN_FILE'                        => 'Open File',
        'KJP_FILE_INFO'                       => 'File Informations',
        'KJP_FILE_NAME'                       => 'File Name',
        'KJP_FILE_OWNR'                       => 'File Owner',
        'KJP_FILE_SZE'                        => 'File Size',
        'KJP_SET_PRC'                         => 'Set Price',
        'KJP_PRC'                             => 'Price',
        'KJP_BUY_FILE'                        => 'Buy File',
        'KJP_BUY'                             => 'Buy',
        'KJP_SCES_PAY'                        => 'SUCCESS PAYMENT',
        'KJP_DOWN_INFO_1'                     => 'Now you can download the file by clicking ',
        'KJP_DOWN_INFO_2'                     => 'a copy of download link sent to mail @mail and its and it will be expire at @time ',
        'KJP_GRP_NAME'                        => 'Group Name',
        'KJP_GRP_JOIN_LNK'                    => 'Join Link',
        'KJP_GRP_INFO'                        => 'To check the allowed extentions of the groups visit',
        'KJP_NO_FILE_WITH_ID'                 => 'No file found with ID' ,
        'KJP_NO_FILE_NEW_PRICE'               => 'the price of file is now' ,
        'KJP_PAY_ID_FALSE'                    => 'There is no Payment with this ID ..' ,
        'KJP_NO_PAID_FILES'                   => 'There is no Paid Files yet ..' ,
        'KJP_NO_PAY_ARCH'                     => 'There is no Payments of Selected Date ..' ,
        'KJP_FILE_TRNCS'                      => 'Transactions of files' ,
        'KJP_GRP_TRNCS'                       => 'Transactions of Joining groups' ,
        'KJP_ARC_PAYS'                        => 'Archive Payments' ,
        'KJP_VIEW_PAY'                        => 'View Payment' ,
        'KJP_PAYR_IP'                         => 'Payer IP' ,
        'KJP_BYNG_FILE'                       => 'Buying File' ,
        'KJP_JUNG_GRP'                        => 'Joining Group' ,
        'KJP_JUIN'                            => 'Join' ,
        'KJP_JUIN_SUCCESS'                    => 'Now you are a member in group' ,
        'KJP_CNT_JOIN'                        => 'you are in this group , you can not join again' ,
        'KJP_GUEST'                           => 'Guest' ,
        'KJP_PID_GRP'                         => 'Paid Groups' ,
        'KJP_PAY_BY_MTHD'                     => 'All Payment of method',
        'KJP_PAY_MTHD'                        => 'Payment Method' ,
        'CONFIG_KLJ_MENUS_KJ_PAY_ACTIVE_MTHD' => 'KJPayments Active Methods',
        'ACTIVE_PAYPAL'                       => 'Active PayPal' ,
        'KJP_MAIL_TPL'                        => "File @fileName bought successfuly \r\n You can download the file from : @downLink \r\n this link will expire at  : @linkExpire",
        'KJP_MAIL'                            => 'Email address',
        'KJP_MAIL_INFO_1'                     => 'Recive Download Link By E-Mail',
        'KJP_MAIL_INFO_2'                     => 'we will send download link to this e-mail',
        'KJP_CANT_JOIN_GRP'                   => 'its not allowed for you to join this group',
        // vars for //view payment// page -> PayPal method details
        'KJP_VIW_TPL_PAYPAL_PAYMENT_ID'       => 'PayPal Payment ID',
        'KJP_VIW_TPL_PAYPAL_PAYER_NAME'       => 'PayPal Payer Name' ,
        'KJP_VIW_TPL_PAYPAL_PAYER_MAIL'       => 'PayPal Payer Mail',
        'KJP_VIW_TPL_PAYPAL_PAYMENT_FEES'     => 'PayPal Payment Fees',
        'KJP_VIW_TPL_PAYPAL_PAYER_ID'         => 'PayPal Payer ID',
    ),
        'en',
        $plg_id);


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
    //delete options
    global $SQL , $dbprefix;

    $SQL->query("ALTER TABLE `{$dbprefix}files` DROP `price`;");


    delete_olang(null, null , $plg_id);

    delete_config(array(
        'join_price',
        'paypal_client_secret',
        'pp_client_id',
        'iso_currency_code',
        'payment_method',
        'active_paypal',
        'down_link_expire',
    ));


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

                    if (  $usrcp->get_data('founder')['founder'] == 0 && !( $row['user'] === $usrcp->id() )  ) 
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
        global $lang , $olang , $usrcp , $config;


        // request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=buy_file&id=1
        // action = buy_file OR join_group or check
        // id = the id of file or the id of group

        // checking request Example : domain.io/kleeja/go.php?go=kj_payment&method=paypal&action=check&blablabla
        // blablabla = it's optional for you to using sessions or anythink you want , anyway it will be global varibles


        if ( ig('go') && g('go') === 'kj_payment' && ig('method') && ig('action') ) 
        {
            require_once dirname(__FILE__) .'/php/kjPayment.php'; // require the payment interface
            $PaymentMethodClass = dirname(__FILE__) . '/method/paypal.php'; // default payment method

            is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:set_payment_method', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

            if ( ! file_exists( $PaymentMethodClass ) ) 
            {
                kleeja_err('The class file of '.g('method').' payment in not found');
                exit;  
            }
            else 
            {
                require_once $PaymentMethodClass;
            }

            $PaymentMethod = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

            // to be sure 
            if ($PaymentMethod !== 'kjPayMethod_' . g('method') ) 
            {
                kleeja_err('Its not your method');
                exit;
            }
            /**
             ** Now we don't need it ,  we only support Create Payments

            $PagePermission = 'createPayment';

            if ( ! $PaymentMethod::permission($PagePermission) ) 
            {
                kleeja_err('This Method Dont Accept Creating Payments');
                exit;
            }

            **/

            $PAY = new $PaymentMethod;

            $PAY->paymentStart(); // Play some song to enjoy ;

            $PAY->setCurrency( strtoupper($config['iso_currency_code']) );

            switch ( g('action') ) {
                case 'buy_file':

                    if ( ! ig('id') ) {
                        kleeja_err($lang['ERROR_NAVIGATATION']);
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


            if (ip('sendMail')) 
            {
                $mailAdress = p('buyerMail');
                if ( ! filter_var($mailAdress, FILTER_VALIDATE_EMAIL)) 
                {
                    kleeja_err('put right e-mail' , '' , true , $config['siteurl'] . 'go.php?go=KJPaymentMailer' , 2);
                    exit; // again :)
                }
                
                $payCookieInfoExplode = explode('_' , $payCookieInfo);

                $fileName = getFileInfo($payCookieInfoExplode[0] , 'real_filename')['real_fileName'];

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
            return compact('stylee' , 'styleePath' , 'no_request' , 'is_style_supported');
        }
        
        


    } , 

    'qr_down_go_page_filename' => function ($args)
    {
        global $SQL , $usrcp , $config;

        $query = $args['query'];
        $query['SELECT'] .= ', f.price';
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

    'Saaheader_links_func' => function ($args) {
        
        $top_menu = $args['top_menu'];

        $top_menu[] = array('name' => 'paid_group', 'title' => $args['olang']['KJP_PID_GRP'], 'url' => 'go.php?go=paid_group', 'show' => true);

        return compact('top_menu');
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

            $paymentInfo = getPaymentInfo( $dbID , "item_id = '{$fileID}' AND payment_token = '{$payToken}' AND payment_state = 'approved'");

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
