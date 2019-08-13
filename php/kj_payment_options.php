<?php
// kleeja plugin
// developer: Kleeja Team


// not for directly open
if (! defined('IN_ADMIN'))
{
    exit;
}

if (intval($userinfo['founder']) !== 1)
{
    kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH . '?cp=' . basename(__FILE__, '.php'));

    exit;
}

$styleePath = dirname(__FILE__) . '/../html/admin/';



$UserById = UserById();

$current_smt    = preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', ''));

if (empty($current_smt))
{
    $FormActions = basename(ADMIN_PATH) . '?cp=kj_payment_options';

    $stylee = 'admin_quick_info';

    if (ip('open_payment'))
    {
        if (ip('payment_number') && p('payment_number', 'int') > 0)
        {
            redirect(basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . p('payment_number'));
        }
    }
    elseif (ip('open_archive'))
    {
        $archive_year  =  p('archive_year', 'int');
        $archive_month =  p('archive_month', 'int');
        $archive_day   =  p('archive_day', 'int') !== 0 ? p('archive_day') . '-' : '';

        redirect(basename(ADMIN_PATH) . "?cp=kj_payment_options&amp;smt=archive&amp;date={$archive_day}{$archive_month}-{$archive_year}");

        exit;
    }

    // add any information u want by this three panels

    $all_trnc_panel     = [/* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */];
    $monthly_trnc_panel = [/* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */];
    $daily_trnc_panel   = [/* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */];


    // all Transactions
    // this function getting informations about transactions that paid by paypal ,except the number of all transactions
    // other method have to calculate they transactions and adding it the CP
    $trncactionsInformation = KJPayFinalData();

    $trnc_count = $trncactionsInformation['kj_payments']['all'];

    $all_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['all']['num']];
    $all_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['all']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $all_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['all']['num']];
    $all_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['all']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $all_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['all']['num']];
    $all_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['all']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];


    //daily Transactions
    $daily_trnc_count = $trncactionsInformation['kj_payments']['daily'];

    $daily_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['daily']['num']];
    $daily_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['daily']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $daily_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['daily']['num']];
    $daily_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['daily']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $daily_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['daily']['num']];
    $daily_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['daily']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];

    // monthly Transactions
    $monthly_trnc_count = $trncactionsInformation['kj_payments']['monthly'];

    $monthly_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['monthly']['num']];
    $monthly_trnc_panel[]   = ['methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['monthly']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $monthly_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['monthly']['num']];
    $monthly_trnc_panel[]   = ['methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['monthly']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];
    $monthly_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['monthly']['num']];
    $monthly_trnc_panel[]   = ['methodName' => 'Balance' , 'htmlContent' => ' ' . $trncactionsInformation['balance']['monthly']['amount'] . ' ' . strtoupper($config['kjp_iso_currency_code'])];




    // add what u want to the panels by this hook using the examples befor

    is_array($plugin_run_result = Plugins::getInstance()->run('kjPay:addToCPanel', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook



    $viewAll_btn   = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions';
    $viewtoday_btn = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions&amp;today=1';
    $viewmonth_btn = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions&amp;thismonth=1';


    // Pending Payments

    $pending_payments = $SQL->query("SELECT id , payment_state , payment_payer_ip , payment_action , item_id , item_name , user , payment_year , payment_month , payment_day , payment_time FROM {$dbprefix}payments WHERE payment_state = 'created'");
    $PendPayNum       = false;

    if ($SQL->num_rows($pending_payments))
    {
        $PendPay = [];

        $PendPayNum = true;

        while ($rows = $SQL->fetch($pending_payments))
        {
            $PayID       = $rows['id'];
            $PayUser     = $rows['user'] > 0 ? $UserById[$rows['user']] : $olang['KJP_GUEST'];
            $PayAction   = $rows['payment_action'] . ' : ' . $rows['item_name'];
            $PayIP       = $rows['payment_payer_ip'];
            $PayDateTime = $rows['payment_year'] . '-' . $rows['payment_month'] . '-' . $rows['payment_day'] . '/' . $rows['payment_time'];

            $PendPay[] = [
                'PayID'       => $PayID ,
                'PayUser'     => $PayUser ,
                'PayAction'   => $PayAction ,
                'PayIP'       => $PayIP ,
                'PayDateTime' => $PayDateTime ,
            ];
        }
    }


    $years     = [];
    $get_years = $SQL->query("SELECT DISTINCT payment_year FROM {$dbprefix}payments WHERE payment_state = 'approved'");

    while ($year = $SQL->fetch($get_years))
    {
        $years[]['value'] = $year['payment_year'];
    }

    if (count($years) == 0)
    {
        $years[]['value'] = date('Y');
    }

    // Lazy person !! Ja Ja , Normalerweise bin ich faul .
    $months = [];

    for ($i=1; $i < 13; $i++)
    {
        $months[]['value'] = $i;
    }

    $days = [];

    for ($i=1; $i < 32; $i++)
    {
        $days[]['value'] = $i;
    }

    // show all transactions .
}
elseif ($current_smt == 'all_transactions')
{
    $stylee = 'all_transactions';

    // get all transactions informations

    $all_trnc_page_title = $olang['KJP_ALL_TRNC'];

    $query = [
        'SELECT'   => 'id ,payment_action , item_id , item_name, user , payment_year , payment_month , payment_day , payment_time' ,
        'FROM'     => $dbprefix . 'payments' ,
        'WHERE'    => "payment_state = 'approved'" ,
        'ORDER BY' => 'id DESC'
    ];
    // show daily transactions

    if (ig('today') && g('today') == 1)
    {
        $query['WHERE'] .= ' AND payment_year = "' . date('Y') . '" AND payment_month = "' . date('m') . '" AND payment_day = "' . date('d') . '"';
        $all_trnc_page_title = $olang['KJP_D_TRNC'];
    }

    // show the transactions of this month
    elseif (ig('thismonth') && g('thismonth') == 1)
    {
        $query['WHERE'] .= ' AND payment_year = "' . date('Y') . '" AND payment_month = "' . date('m') . '"';
        $all_trnc_page_title = $olang['KJP_M_TRNC'];
    }
    // show al transactions of buying file
    // never never think to make this msg dynamic ,
    // we did not get anything from the DB yet , and sprintf function is not useful here
    elseif (ig('action') && g('action') == 'buy_file' && ig('item_id'))
    {
        $query['WHERE'] .= ' AND payment_action = "buy_file" AND item_id = "' . g('item_id') . '"';
        $all_trnc_page_title = sprintf($olang['KJP_PAY_OF'], sprintf($olang['KJP_ACT_BUY_FILE'], getFileInfo(g('item_id'), 'real_filename')['name'])); // i didn't find another way :( -> connect
    }
    // show all transactions of joining group .
    elseif (ig('action') && g('action') == 'join_group' && ig('item_id'))
    {
        $query['WHERE'] .= ' AND payment_action = "join_group" AND item_id = "' . g('item_id') . '"';
        // if the group become for free later , we can not see the group name
        $all_trnc_page_title = sprintf($olang['KJP_PAY_OF'], sprintf($olang['KJP_ACT_JOIN_GROUP'], getGroupInfo($d_groups, g('item_id'))['name']));
    }
    // contenue here
    elseif (ig('action') && g('action') !== 'join_group' && g('action') !== 'buy_file' && ig('item_id'))
    {
        $query['WHERE'] .= ' AND payment_action = "' . g('action') . '" AND item_id = "' . g('item_id') . '"';
        // export this msg only ,,,$all_trnc_page_title 
        is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:allTrnc_' . g('action'), get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    }


    // show all transactions of this user
    elseif (ig('user') && (int) g('user'))
    {
        $query['WHERE'] .= ' AND user = "' . g('user') . '"';
        $all_trnc_page_title = $olang['KJP_USR_PAYMNT'] . ' : ' . strtoupper($UserById[g('user')]);
    }
    // if the buyer of the file is not member , we can bring his/her payments by IP .
    elseif (ig('ip') && (int) g('ip'))
    {
        $query['WHERE'] .= ' AND payment_payer_ip = "' . g('ip') . '"';
        $all_trnc_page_title = $olang['KJP_IP_PAYMNT'] . ' : ' . g('ip');
    }
    // to check the payments that used this method
    elseif (ig('method'))
    {
        $query['WHERE'] .= ' AND payment_method = "' . g('method') . '"';
        $all_trnc_page_title = $olang['KJP_PAY_BY_MTHD'] . ' : ' . $olang['KJP_MTHD_NAME_' . strtoupper(g('method'))];
    }

    $all_result       = $SQL->build($query);
    $have_transaction = $page_nums = false;

    if ($num_rows = $SQL->num_rows($all_result))
    {
        // Pagination //

        $perpage               = 21;
        $currentPage           = ig('page') ? g('page', 'int') : 1;
        $Pager                 = new Pagination($perpage, $num_rows, $currentPage);
        $start                 = $Pager->getStartRow();
        $linkgoto              = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions';
        $linkgoto    .= (ig('today') ? '&amp;today=1' : (ig('file') ? '&amp;file=' . g('file') : (ig('group') ? '&amp;group=' . g('group') : (ig('thismonth') ? '&amp;thismonth=' . g('thismonth') : (ig('user') ? '&amp;user=' . g('user') : (ig('ip') ? '&amp;ip=' . g('ip') : (ig('method') ? '&amp;method=' . g('method') : null)))))));
        $page_nums            = $Pager->print_nums($linkgoto);
        $query['LIMIT']       = "$start, $perpage";
        $all_result           = $SQL->build($query);



        $have_transaction = true;
        $transactions     = [];

        while ($trnc = $SQL->fetch($all_result))
        {
            $PayID       = $trnc['id'];
            $PayUser     = $trnc['user'] > 0 ? $UserById[$trnc['user']] : $olang['KJP_GUEST'];
            $PayAction   = sprintf($olang['KJP_ACT_' . strtoupper($trnc['payment_action'])], $trnc['item_name']);
            $PayDateTime = $trnc['payment_year'] . '-' . $trnc['payment_month'] . '-' . $trnc['payment_day'] . '/' . $trnc['payment_time'];

            $transactions[] = [
                'PayID'       => $PayID ,
                'PayUser'     => $PayUser ,
                'PayAction'   => $PayAction ,
                'PayDateTime' => $PayDateTime ,
                'view_link'   => basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . $PayID
            ];
        }
    }


    // view payment details ..
}
elseif ($current_smt == 'view' && (int) g('payment'))
{
    $stylee  = 'view_payment';
    $PayInfo = getPaymentInfo(g('payment'), "payment_state = 'approved'");

    if (! $PayInfo)
    {
        $have_payment = false;
    }
    else
    {
        $have_payment = true;

        $id                 = $PayInfo['id'];
        $amount             = $PayInfo['payment_amount'] . ' ' . $PayInfo['payment_currency'];
        $token              = $PayInfo['payment_token'];
        $payment_method     = $olang['KJP_MTHD_NAME_' . strtoupper($PayInfo['payment_method'])];
        $payer_mail         = $PayInfo['payment_payer_mail'];
        $payer_ip           = $PayInfo['payment_payer_ip'];

        $item        = sprintf($olang['KJP_ACT_' . strtoupper($PayInfo['payment_action'])], $PayInfo['item_name']);

        $member      = $PayInfo['user'] > 0 ? '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=g_users&smt=edit_user&uid=' . $PayInfo['user'] . '">' . $UserById[$PayInfo['user']] . '</a>'
                     : $olang['KJP_GUEST'];

        $date_time    = $PayInfo['payment_year'] . '-' . $PayInfo['payment_month'] . '-' . $PayInfo['payment_day'] . ' / ' . $PayInfo['payment_time'];

        $file_payments = '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=all_transactions&action=' . $PayInfo['payment_action'] . '&item_id=' . $PayInfo['item_id'] . '">' . sprintf($olang['KJP_PAY_OF'], sprintf($olang['KJP_ACT_' . strtoupper($PayInfo['payment_action'])], $PayInfo['item_name'])) . '</a>';

        $user_payments = $PayInfo['user'] > 0 ?
        '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=all_transactions&user=' . $PayInfo['user'] . '">' . $olang['KJP_USR_PAYMNT'] . ' : ' . $UserById[$PayInfo['user']] . '</a>'
        : '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=all_transactions&ip=' . $payer_ip . '">' . $olang['KJP_IP_PAYMNT'] . ' : ' . $payer_ip . '</a>';

        $method_payments = '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=all_transactions&method=' . $PayInfo['payment_method'] . '">' . $olang['KJP_PAY_BY_MTHD'] . ' : ' . $olang['KJP_MTHD_NAME_' . strtoupper($PayInfo['payment_method'])] . '</a>';

        $viewMoreTable     = []; // evry method have some informations
        $methodPaymentInfo = [];

        $methodPaymentInfo['payment_more_info'] = $PayInfo['payment_more_info']; // we don't want to get all information again :: omly the method informations

        foreach (payment_more_info('from_db', $methodPaymentInfo) as $key => $value)
        {
            $viewMoreTable[] = [
                'tableName'  => $olang['KJP_VIW_TPL_' . strtoupper($key)] ?? strtoupper($key),
                'tableValue' => $value
            ];
        }
    }



    // set a price for file .
}
elseif ($current_smt == 'pricing_file')
{
    $stylee = 'add_price';


    if (ip('open_file'))
    {
        $select_file_id =  ip('select_file_id') ? p('select_file_id') : null;

        $ExampleID  = $config['siteurl'] . 'do.php?id=';
        $ExampleIMG = $config['siteurl'] . 'do.php?img=';


        if (! (int) $select_file_id)
        {
            $select_file_id = str_replace([$ExampleID , $ExampleIMG], '', $select_file_id);
        }

        if ($select_file_id !== null && $select_file_id > 0 && $file_info = getFileInfo($select_file_id))
        {
            $show_price_panel = true;
            $OpenAlert        = false;

            $price_file_image = ''; //TODO!
            $FileID           = $file_info['id'];
            $FileName         = $file_info['name'];
            $FileSize         = readable_size($file_info['size']);
            $FileUser         = $file_info['user'] > 0 ? $UserById[$file_info['user']] : $olang['KJP_GUEST'];
            $FilePrice        = $file_info['price'];
        }
        else
        {
            $show_price_panel = false;
            $OpenAlert        = true;
            $AlertMsg         = $olang['KJP_NO_FILE_WITH_ID'] . $select_file_id;
            $AlertRole        = 'danger';
        }
    }
    elseif (ip('set_price'))
    {
        $FileID    = (int) p('price_file_id');
        $FileName  = p('file_name');
        $FilePrice = p('price_file');

        if ($file_info = getFileInfo($FileID))
        {
            $update_query = [
                'UPDATE' => $dbprefix . 'files' ,
                'SET'    => "price = '{$FilePrice}'" ,
                'WHERE'  => "id = '{$FileID}' AND real_filename = '{$FileName}'"
            ];

            $SQL->build($update_query);

            if ($SQL->affected())
            {
                $OpenAlert = true;
                $AlertMsg  = sprintf($olang['KJP_NO_FILE_NEW_PRICE'], $FileName, $FilePrice, strtoupper($config['kjp_iso_currency_code']));
                $AlertRole = 'success';
            }
            else
            {
                $OpenAlert = true;
                $AlertMsg  = $olang['KJP_NO_FILE_WITH_ID'] . ' ' . $FileID;
                $AlertRole = 'danger';
            }
        }
    }
}
elseif ($current_smt == 'paid_files')
{
    $stylee = 'paid_files';

    $all_paid_file = [];

    $query = [
        'SELECT' => 'id , real_filename , user , price' ,
        'FROM'   => "{$dbprefix}files" ,
        'WHERE'  => 'price > 0'
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
        $linkgoto                = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=paid_files';
        $page_nums               = $Pager->print_nums($linkgoto);
        $query['LIMIT']          = "$start, $perpage";
        $paid_f                  = $SQL->build($query);



        $have_paid_file = true;
        while ($paid_file = $SQL->fetch($paid_f))
        {
            $all_paid_file[] = [
                'id'    => $paid_file['id'] ,
                'name'  => $paid_file['real_filename'] ,
                'user'  => $paid_file['user'] > 0 ? $UserById[$paid_file['user']] : $olang['KJPP_GUEST'] ,
                'price' => $paid_file['price'] . ' ' . $config['kjp_iso_currency_code'],
                'link'  => $config['siteurl'] . 'do.php?id=' . $paid_file['id']
            ];
        }
    }
}
elseif ($current_smt == 'archive' && ig('date'))
{
    $stylee = 'archive_data';

    $archive_date = g('date');
    $Archive_data = get_archive($archive_date);

    $archiveTables = [];

    foreach ($Archive_data['paymentActions'] as $key => $value)
    {
        $archiveTables[] = ['html' => create_Archive_Panel($key, $value, ($key == 'all' ? true : false))];
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('kjPay:addToArchive', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook



    $query = $Archive_data['query'];

    $query['SELECT'] .= ', id , payment_state , payment_payer_ip , item_id , item_name , user , payment_time';
    $query['ORDER BY'] = 'id DESC';


    $archive_payments = $SQL->build($query);
    $ArchivePayNum    = $page_nums    = false;

    if ($num_rows = $SQL->num_rows($archive_payments))
    {
        // Pagination
        $perpage                   = 21;
        $currentPage               = ig('page') ? g('page', 'int') : 1;
        $Pager                     = new Pagination($perpage, $num_rows, $currentPage);
        $start                     = $Pager->getStartRow();
        $linkgoto                  = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=archive&date=' . g('date');
        $page_nums                 = $Pager->print_nums($linkgoto);
        $query['LIMIT']            = "$start, $perpage";
        $archive_payments          = $SQL->build($query);

        $ArchivePay    = [];
        $ArchivePayNum = true;

        while ($rows = $SQL->fetch($archive_payments))
        {
            $PayID       = $rows['id'];
            $PayUser     = $rows['user'] > 0 ? $UserById[$rows['user']] : $olang['KJP_GUEST'];
            $PayAction   = sprintf($olang['KJP_ACT_' . strtoupper($rows['payment_action'])], $rows['item_name']);
            $PayIP       = $rows['payment_payer_ip'];
            $PayDateTime = $rows['payment_year'] . '-' . $rows['payment_month'] . '-' . $rows['payment_day'] . '/' . $rows['payment_time'];

            $ArchivePay[] = [
                'PayID'       => $PayID ,
                'PayUser'     => $PayUser ,
                'PayAction'   => $PayAction ,
                'PayIP'       => $PayIP ,
                'PayDateTime' => $PayDateTime ,
                'view_link'   => basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . $PayID
            ];
        }
    }


    // Archive Payout Table

    $query = [
        'SELECT' => '*' ,
        'FROM'   => "{$dbprefix}payments_out",
        'WHERE'  => "`payout_year` = '{$Archive_data['date']['year']}' AND `payout_month` = '{$Archive_data['date']['month']}'"
         . (! empty($Archive_data['date']['day']) ? " AND `payout_day` = '{$Archive_data['date']['day']}'":''),
        'ORDER BY' => 'id DESC'
    ];

    $result     = $SQL->build($query);
    $havePayout = $page_nums = false;

    if ($num_rows = $SQL->num_rows($result))
    {
        $perpage                 = 21;
        $currentPage             = ig('page') ? g('page', 'int') : 1;
        $Pager                   = new Pagination($perpage, $num_rows, $currentPage);
        $start                   = $Pager->getStartRow();
        $linkgoto                = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=archive&date=' . g('date');
        $page_numsPO             = $Pager->print_nums($linkgoto);
        $query['LIMIT']          = "$start, $perpage";
        $result                  = $SQL->build($query);


        $payouts    = [];
        $havePayout = true;
        while ($row = $SQL->fetch_array($result))
        {
            $payouts[] = [
                'ID'         => $row['id'],
                'METHOD'     => $row['method'],
                'AMOUNT'     => $row['amount'] . ' ' . $config['kjp_iso_currency_code'],
                'DATE_TIME'  => "{$row['payout_year']}-{$row['payout_month']}-{$row['payout_day']} / {$row['payout_time']}",
                'STATE'      => $row['state'],
                'PayoutUser' => $UserById[$row['user']]
            ];
        }
    }
}
elseif ($current_smt == 'payouts')
{
    $stylee = 'payouts_list';
    $action = $config['siteurl'] . 'admin/index.php?cp=kj_payment_options&smt=payouts';
    $case   = g('case', 'str', 'list');

    if ($case == 'payouts')
    {
        // dont do any thing now
    }
    elseif ($case == 'list')
    {
        // lets check if there is post order
        // for sending payout or canceling it
        if ((ip('sendPayout') || ip('cancelPayout')) && ip('payoutID'))
        {
            $checkQuery = [
                'SELECT' => '*', // leave it all , we need every thing here
                'FROM'   => "{$dbprefix}payments_out",
                'WHERE'  => "id = '" . p('payoutID') . "' AND state = 'verify'",
            ];
            $checkResult = $SQL->build($checkQuery);
            // if we had this payout in db
            if ($SQL->num_rows($checkResult))
            {
                $pOutInfo = $SQL->fetch($checkResult);
                // mix all info to make usfule array
                $pOutInfo = payment_more_info('from_db', $pOutInfo);
                // check if admin want to send or cancel it
                // more secure to do it like this
                // here is for canceling payout
                if (ip('cancelPayout') && ! ip('sendPayout'))
                {
                    //let's update the payout state and back the amount to user balance
                    $SQL->query("UPDATE {$dbprefix}users SET `balance` = balance+{$pOutInfo['amount']} WHERE id ='{$pOutInfo['user']}'");
                    $SQL->query("UPDATE {$dbprefix}payments_out SET `state` = 'cancel' WHERE id = '" . p('payoutID') . "'");
                    kleeja_admin_info(sprintf($olang['KJP_CNCLD_POUT'], $pOutInfo['amount']), $action . '&amp;case=list');

                    exit;
                }
                // the admin accept sending this amount to user
                elseif (ip('sendPayout') && ! ip('cancelPayout'))
                {
                    require_once dirname(__FILE__) . '/kjPayment.php'; // require the payment interface
                    $PaymentMethodClass = dirname(__FILE__) . '/../method/' . $pOutInfo['method'] . '.php'; // default payment method

                    if (! file_exists($PaymentMethodClass))
                    {
                        $is_err = true;
                        is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:set_payout_method', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                        if ($is_err)
                        {
                            kleeja_admin_err('The class file of ' . g('method') . ' payment is not found');

                            exit;
                        }
                    }
                    require_once $PaymentMethodClass;

                    $methodClassName = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

                    $PAY = new $methodClassName;
                    $PAY->paymentStart();
                    $PAY->setCurrency(strtoupper($config['kjp_iso_currency_code']));
                    // now let's make a payout
                    $PAY->createPayout($pOutInfo); // send all payout data to the class

                    if ($PAY->isSuccess())
                    {
                        kleeja_admin_info(sprintf($olang['KJP_SUCS_POUT'], $pOutInfo['amount']), $action . '&amp;case=list');

                        exit;
                    }
                    else
                    {
                        kleeja_admin_err($olang['KJP_ERR_POUT'], $action . '&amp;case=list');
                    }
                }
            }
        }


        $query = [
            'SELECT'   => '*',
            'FROM'     => "{$dbprefix}payments_out",
            'WHERE'    => "state = 'verify'",
            'ORDER BY' => 'id DESC'
        ];

        $result        = $SQL->build($query);
        $havePayout    = $page_nums    = false;
        $no_payout_msg = sprintf($olang['KJP_NO_ITEM'], $olang['KJP_PAYOUTS']);

        if ($num_rows = $SQL->num_rows($result))
        {
            $perpage                 = 21;
            $currentPage             = ig('page') ? g('page', 'int') : 1;
            $Pager                   = new Pagination($perpage, $num_rows, $currentPage);
            $start                   = $Pager->getStartRow();
            $linkgoto                = basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=payouts&case=accepted';
            $page_nums               = $Pager->print_nums($linkgoto);
            $query['LIMIT']          = "$start, $perpage";
            $result                  = $SQL->build($query);

            $havePayout = true;
            $payouts    = [];
            while ($payout = $SQL->fetch_array($result))
            {
                $payouts[] = [
                    'ID'        => $payout['id'],
                    'USER'      => $UserById[$payout['user']],
                    'METHOD'    => $payout['method'],
                    'AMOUNT'    => $payout['amount'] . ' ' . $config['kjp_iso_currency_code'],
                    'DATE_TIME' => "{$payout['payout_year']}-{$payout['payout_month']}-{$payout['payout_day']} / {$payout['payout_time']}",
                    'STATE'     => $payout['state'],
                    'MDL_MSG'   => sprintf($olang['KJP_POUT_REQ_MDL'], $UserById[$payout['user']], $payout['amount'] . ' ' . $config['kjp_iso_currency_code'], $payout['method']),
                ];
            }
        }
    }
    elseif ($case == 'accepted')
    {
        $query = [
            'SELECT'   => '*',
            'FROM'     => "{$dbprefix}payments_out",
            'WHERE'    => "state = 'sent' OR state = 'recived'",
            'ORDER BY' => 'id DESC'
        ];

        $result            = $SQL->build($query);
        $havePayout        = $page_nums        = false;
        $no_acc_payout_msg = sprintf($olang['KJP_NO_ITEM'], $olang['KJP_ACCEPTED'] . ' ' . $olang['KJP_PAYOUTS']);

        if ($num_rows = $SQL->num_rows($result))
        {
            $perpage                 = 21;
            $currentPage             = ig('page') ? g('page', 'int') : 1;
            $Pager                   = new Pagination($perpage, $num_rows, $currentPage);
            $start                   = $Pager->getStartRow();
            $linkgoto                = basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=payouts&case=accepted';
            $page_nums               = $Pager->print_nums($linkgoto);
            $query['LIMIT']          = "$start, $perpage";
            $result                  = $SQL->build($query);

            $payouts    = [];
            $havePayout = true;
            while ($payout = $SQL->fetch_array($result))
            {
                $payouts[] = [
                    'ID'        => $payout['id'],
                    'USER'      => $UserById[$payout['user']],
                    'METHOD'    => $payout['method'],
                    'AMOUNT'    => $payout['amount'] . ' ' . $config['kjp_iso_currency_code'],
                    'DATE_TIME' => "{$payout['payout_year']}-{$payout['payout_month']}-{$payout['payout_day']} / {$payout['payout_time']}",
                    'VIEW_LINK' => $config['siteurl'] . 'admin/index.php?cp=kj_payment_options&smt=viewPayout&amp;id=' . $payout['id']
                ];
            }
        }
    }
    elseif ($case == 'canceled')
    {
        $query = [
            'SELECT'   => '*',
            'FROM'     => "{$dbprefix}payments_out",
            'WHERE'    => "state = 'cancel'",
            'ORDER BY' => 'id DESC'
        ];

        $result             = $SQL->build($query);
        $havePayout         = $page_nums         = false;
        $no_cncl_payout_msg = sprintf($olang['KJP_NO_ITEM'], $olang['KJP_CANCELED'] . ' ' . $olang['KJP_PAYOUTS']);

        if ($num_rows = $SQL->num_rows($result))
        {
            $perpage                 = 21;
            $currentPage             = ig('page') ? g('page', 'int') : 1;
            $Pager                   = new Pagination($perpage, $num_rows, $currentPage);
            $start                   = $Pager->getStartRow();
            $linkgoto                = basename(ADMIN_PATH) . '?cp=kj_payment_options&smt=payouts&case=canceled';
            $page_nums               = $Pager->print_nums($linkgoto);
            $query['LIMIT']          = "$start, $perpage";
            $result                  = $SQL->build($query);

            $payouts    = [];
            $havePayout = true;
            while ($payout = $SQL->fetch_array($result))
            {
                $payouts[] = [
                    'ID'        => $payout['id'],
                    'USER'      => $UserById[$payout['user']],
                    'METHOD'    => $payout['method'],
                    'AMOUNT'    => $payout['amount'] . ' ' . $config['kjp_iso_currency_code'],
                    'DATE_TIME' => "{$payout['payout_year']}-{$payout['payout_month']}-{$payout['payout_day']} / {$payout['payout_time']}",
                    'VIEW_LINK' => $config['siteurl'] . 'admin/index.php?cp=kj_payment_options&smt=viewPayout&amp;id=' . $payout['id']
                ];
            }
        }
    }
}
elseif ($current_smt == 'viewPayout' && ig('id'))
{
    $stylee    = 'view_payout';
    $poutID    = preg_replace('/[^a-z0-9_]/i', '', g('id', 'int', ''));

    $payoutInfo  = getpayoutInfo($poutID, "state != 'verify'", false);
    $have_payout = false;

    if ($payoutInfo)
    {
        if (ip('checkPayout') && ip('payoutID') && p('payoutID') == $payoutInfo['id'])
        {
            require_once dirname(__FILE__) . '/kjPayment.php'; // require the payment interface
            $PaymentMethodClass = dirname(__FILE__) . '/../method/' . $payoutInfo['method'] . '.php'; // default payment method

            if (! file_exists($PaymentMethodClass))
            {
                $is_err = true;
                is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:check_payout', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

                if ($is_err)
                {
                    kleeja_admin_err('The class file of ' . $payoutInfo['method'] . ' payment is not found');

                    exit;
                }
            }
            require_once $PaymentMethodClass;

            $methodClassName = 'kjPayMethod_' . basename($PaymentMethodClass, '.php');

            $PAY = new $methodClassName;
            $PAY->paymentStart();
            $PAY->setCurrency(strtoupper($config['kjp_iso_currency_code']));
            $PAY->checkPayout(payment_more_info('from_db', $payoutInfo));

            if ($PAY->isSuccess())
            {
                kleeja_admin_info($olang['KJP_POUT_RCV_SUCS']);
            }
            else
            {
                kleeja_admin_err($olang['KJP_POUT_NOT_RCV_SUCS']);
            }
        }
        $FormAction  = $config['siteurl'] . 'admin/index.php?cp=kj_payment_options&smt=viewPayout&id=' . $payoutInfo['id'];
        $have_payout = true;

        $payout_id        = $payoutInfo['id'];
        $payout_user      = $UserById[$payoutInfo['user']];
        $payout_method    = $payoutInfo['method'];
        $payout_amount    = $payoutInfo['amount'] . ' ' . $config['kjp_iso_currency_code'];
        $payout_date_time = $payoutInfo['payout_year'] . '-' . $payoutInfo['payout_month'] . '-' . $payoutInfo['payout_day']
                            . ' / ' . $payoutInfo['payout_time'];
        $payout_state      = $payoutInfo['state'];
        $payment_more_info = payment_more_info('from_db', ['payment_more_info' => $payoutInfo['payment_more_info']]);

        $viewMoreTable = [];

        foreach ($payment_more_info as $key => $value)
        {
            $viewMoreTable[] = [
                'tableName'  => $olang['KJP_VIW_TPL_' . strtoupper($key)] ?? strtoupper($key),
                'tableValue' => $value
            ];
        }
    }
}
elseif ($current_smt == 'help')
{
    $stylee   = 'help';
    $KJP_HELP = [/* [ 'ID' => 'Example_ID' , 'TITLE' => 'Example Title', 'CONTENT' => 'Example Content'] */];
    is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:KLJ_HELP', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
}
elseif ($current_smt == 'subscriptions')
{

    // dont disply `subscriptions` pages when it's disabled
    if (! $config['kjp_active_subscriptions']): kleeja_admin_err($olang['KJP_SUBSCRIP_NOT_ACTIVE'], basename(ADMIN_PATH) . '?cp=kj_payment_options');

    exit;
    endif;
    $stylee   = 'subscriptions';
    $action   = $config['siteurl'] . 'admin/index.php?cp=kj_payment_options&smt=subscriptions';
    $case     = g('case', 'str', 'create');

    switch ($case) {
        case 'create':

            // let's create new subscription
            if (ip('create_subscription'))
            {
                $subscrip_name  = p('subscription_name');
                $subscrip_days  = p('subscription_time');
                $subscrip_price = p('subscription_price');

                if ('' == $subscrip_name)
                {
                    kleeja_admin_err('Empty name', $action);

                    exit;
                }
                elseif ('' == $subscrip_days || ! (int) $subscrip_days)
                {
                    kleeja_admin_err('invaled time', $action);

                    exit;
                }
                elseif ('' == $subscrip_price || ! (float) $subscrip_price)
                {
                    kleeja_admin_err('invaled Price', $action);

                    exit;
                }

                $subscrip_auery = [
                    'INSERT' => 'name , days , price',
                    'INTO'   => "{$dbprefix}subscriptions",
                    'VALUES' => "'{$subscrip_name}' , {$subscrip_days} , {$subscrip_price}",
                ];
                $SQL->build($subscrip_auery);

                if ($SQL->affected())
                {
                    kleeja_admin_info($olang['KJP_SUBSCRIBE_CREAT_SUCCESS'], $action);

                    exit;
                }
            }

            break;

        case 'subscription_list':

            $get_subscriptions_query = [
                'SELECT'   => '*' ,
                'FROM'     => "{$dbprefix}subscriptions",
                'ORDER BY' => 'id DESC'
            ];

            // subs => subscriptions

            $subsResult = $SQL->build($get_subscriptions_query);
            $page_nums  = $have_subscriptions  = false;

            if ($num_rows = $SQL->num_rows($subsResult))
            {
                // Pagination //

                $perpage                           = 21;
                $currentPage                       = ig('page') ? g('page', 'int') : 1;
                $Pager                             = new Pagination($perpage, $num_rows, $currentPage);
                $start                             = $Pager->getStartRow();
                $linkgoto                          = $action . '&case=subscription_list';
                $page_nums                         = $Pager->print_nums($linkgoto);
                $get_subscriptions_query['LIMIT']  = "$start, $perpage";
                $subsResult                        = $SQL->build($get_subscriptions_query);

                $subscription = [];

                $have_subscriptions  = true;
                while ($subs = $SQL->fetch($subsResult))
                {
                    $subscription[] = [
                        'ID'      => $subs['id'],
                        'NAME'    => $subs['name'],
                        'DAYS'    => $subs['days'],
                        'PRICE'   => $subs['price'],
                        'ACTION'  => '<a class="btn btn-primary" href="' . $action . '&case=view&pack=' . $subs['id'] . '">' . $olang['KJP_VIEW'] . '</a>',
                    ];
                }
            }

            break;

            case 'view':

            // if no package isset in the url , return 
            if (! ig('pack'))
            {
                kleeja_admin_err('NO Pack', $action);

                exit;
            }

            $package       = g('pack', 'int');

            // if try to delete
            if (ip('delete_package'))
            {
                // let's ckeck the form keys
                if (! kleeja_check_form_key('DELETE_' . $package)
                || ! kleeja_check_form_key_get('DELETE_' . $package))
                {
                    kleeja_admin_err($lang['INVALID_FORM_KEY'], $action . '&case=view&pack=' . $package);

                    exit;
                }
                // delete subscripe
                $SQL->query("DELETE FROM {$dbprefix}subscriptions WHERE id = {$package}");

                if ($SQL->affected())
                {
                    // update the package of users who using this package
                    $SQL->query("UPDATE {$dbprefix}users SET package = 0 WHERE package = $package");

                    kleeja_admin_info(sprintf($olang['KJP_PACK_DELETE_SUCCESS'], $package), $action);

                    exit;
                }
            }
            $formAction    = $action . '&case=view&pack=' . $package . '&';
            $kjFormKeyPost = kleeja_add_form_key('DELETE_' . $package);
            $kjFormKeyGet  = kleeja_add_form_key_get('DELETE_' . $package);

            $query = [
                'SELECT'   => '*' ,
                'FROM'     => "{$dbprefix}subscriptions",
                'WHERE'    => "id = {$package}"
            ];


            $packageContent = $SQL->build($query);

            if (! $SQL->num_rows($packageContent)) : kleeja_admin_err('NO PACKAGE FOUND WITH ID ' . $package); endif;


            $packContent                 = $SQL->fetch($packageContent);
            $packContent['MembersCount'] = $subscription->getMembersCount($package);


            break;

        default:
            // code...
            break;
    }
}

$go_menu = [
    'all_transactions' => ['name'=> $olang['KJP_ALL_TRNC'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions', 'goto'=>'all_transactions', 'current'=> $current_smt == 'all_transactions'],
    'payouts'          => ['name'=> $olang['KJP_PAYOUTS'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=payouts', 'goto'=>'payouts', 'current'=> $current_smt == 'payouts'],
    'pricing_file'     => ['name'=> $olang['KJP_PRC_FILE'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=pricing_file', 'goto'=>'pricing_file', 'current'=> $current_smt == 'pricing_file'],
    'paid_files'       => ['name'=> $olang['KJP_PAID_FILE'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=paid_files', 'goto'=>'paid_files', 'current'=> $current_smt == 'paid_files'],
    'help'             => ['name'=> $olang['KJP_HLP'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=help', 'goto'=>'help', 'current'=> $current_smt == 'help'],
];
