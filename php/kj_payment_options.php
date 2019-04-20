<?php
# kleeja plugin
# Kleeja PayPal Pack
# version: 1.0
# developer: Kleeja Team :)


// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

if (intval($userinfo['founder']) !== 1) {
	kleeja_admin_err($lang['HV_NOT_PRVLG_ACCESS'], ADMIN_PATH.'?cp='.basename(__FILE__, '.php'));
	exit;
}

is_array($plugin_run_result = Plugins::getInstance()->run('kjPay:begin_options', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

$styleePath = dirname(__FILE__) . '/../html/admin/' ;



$UserById = UserById();

$current_smt	= preg_replace('/[^a-z0-9_]/i', '', g('smt', 'str', ''));

if ( empty($current_smt)) 
{

	$FormActions = basename(ADMIN_PATH) . '?cp=kj_payment_options';

	$stylee = 'admin_quick_info' ;

	if (ip('open_payment')) {

		(int) p('payment_number') ?
		redirect(basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . p('payment_number') )
		: null;

	}elseif ( ip('open_archive') ) {
		$archive_year  =  p('archive_year');
		$archive_month =  p('archive_month');
		$archive_day   =  p('archive_day') !=='' ? p('archive_day') . '-' : '';

		redirect(basename(ADMIN_PATH) . "?cp=kj_payment_options&amp;smt=archive&amp;date={$archive_day}{$archive_month}-{$archive_year}");
		exit;

	}

	// add any information u want by this three panels

	$all_trnc_panel     = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 
	$monthly_trnc_panel = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 
	$daily_trnc_panel   = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 


	// all Transactions
	# this function getting informations about transactions that paid by paypal ,except the number of all transactions
	// other method have to calculate they transactions and adding it the CP
	$trncactionsInformation = KJPayFinalData();

	$trnc_count = $trncactionsInformation['kj_payments']['all'];

	$all_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['all']['num']  ); 
	$all_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['all']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 
	$all_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['all']['num']  ); 
	$all_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['all']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 


	//daily Transactions
	$daily_trnc_count = $trncactionsInformation['kj_payments']['daily'];

	$daily_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['daily']['num']  ); 
	$daily_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['daily']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 
	$daily_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['daily']['num']  ); 
	$daily_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['daily']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 

	// monthly Transactions
	$monthly_trnc_count = $trncactionsInformation['kj_payments']['monthly'];

	$monthly_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => ' ' . $trncactionsInformation['paypal']['monthly']['num']  ); 
	$monthly_trnc_panel[]   = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $trncactionsInformation['paypal']['monthly']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 
	$monthly_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['monthly']['num']  ); 
	$monthly_trnc_panel[]   = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $trncactionsInformation['cards']['monthly']['amount'] . ' ' . strtoupper($config['iso_currency_code'])); 




	// add what u want to the panels by this hook using the examples befor 

	is_array($plugin_run_result = Plugins::getInstance()->run('kjPay:add_to_panels', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook



	$viewAll_btn = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions' ;
	$viewtoday_btn = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions&amp;today=1' ;
	$viewmonth_btn = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions&amp;thismonth=1' ;


	// Pending Payments

$pending_payments = $SQL->query("SELECT id , payment_state , payment_payer_ip , payment_action , item_id , item_name , user , payment_year , payment_month , payment_day , payment_time FROM {$dbprefix}payments WHERE payment_state = 'created'");
$PendPayNum = false;

if ( $SQL->num_rows($pending_payments)) {

	$PendPay = array();

	$PendPayNum = true;

	while ($rows = $SQL->fetch($pending_payments) ) {

		$PayID = $rows['id'];
		$PayUser = $rows['user'] > 0 ? $UserById[$rows['user']] : $olang['KJP_GUEST'];
		$PayAction = $rows['payment_action'] . ' : ' . $rows['item_name'];
		$PayIP = $rows['payment_payer_ip'];
		$PayDateTime = $rows['payment_year'] . '-' . $rows['payment_month'] . '-' . $rows['payment_day'] . '/' . $rows['payment_time'] ;

		$PendPay[] = array(
			'PayID' => $PayID ,
			'PayUser' => $PayUser ,
			'PayAction' => $PayAction ,
			'PayIP' => $PayIP ,
			'PayDateTime' => $PayDateTime ,
		);

	}
}
	
	
$years = array();
$get_years = $SQL->query("SELECT DISTINCT payment_year FROM {$dbprefix}payments WHERE payment_state = 'approved'");

while ($year = $SQL->fetch($get_years) ) {
	$years[]['value'] = $year['payment_year'];
}

// Lazy person !! Ja Ja , Normalerweise bin ich faul . 
$months = array();
for ($i=1; $i < 13; $i++) { 
	$months[]['value'] = $i;
}

$days = array();
for ($i=1; $i < 32; $i++) { 
	$days[]['value'] = $i;
}

// show all transactions .

}
elseif ( $current_smt == 'all_transactions' ) 
{
	
	$stylee = 'all_transactions' ;

	// get all transactions informations

	$all_trnc_page_title = $olang['KJP_ALL_TRNC'];

	$query = array(
		'SELECT' => 'id ,payment_action , item_id , item_name, user , payment_year , payment_month , payment_day , payment_time' ,
		'FROM' => $dbprefix . 'payments' ,
		'WHERE' => "payment_state = 'approved'" ,
		'ORDER BY' => 'id DESC'
	);
	# show daily transactions

	if (ig('today') && g('today') == 1 )
	{
		$query['WHERE'] .= ' AND payment_year = "' . date('Y') . '" AND payment_month = "' . date('m') . '" AND payment_day = "' . date('d') . '"';
		$all_trnc_page_title = $olang['KJP_D_TRNC'];
	}

	// show the transactions of this month
	elseif (ig('thismonth') && g('thismonth') == 1 )
	{
		$query['WHERE'] .= ' AND payment_year = "' . date('Y') . '" AND payment_month = "' . date('m') . '"';
		$all_trnc_page_title = $olang['KJP_M_TRNC'];
	}
		# show al transactions of buying file
	elseif (ig('file') && (int) g('file') ) {
		$query['WHERE'] .= ' AND payment_action = "buy_file" AND item_id = "' . g('file') . '"';
		$all_trnc_page_title = $olang['KJP_FILE_PAYMNT'] . ' : ' . getFileInfo( g('file') , 'real_filename' )['real_filename']; // i didn't find another way :( -> connect
	}
	// show all transactions of joining group .
  elseif (ig('group') && (int) g('group') ) {
		$query['WHERE'] .= ' AND payment_action = "join_group" AND item_id = "' . g('group') . '"';
		$all_trnc_page_title = $olang['KJP_GRP_PAYMNT'] . ' : ' . getGroupInfo($d_groups , g('group'))['name']; // if the group become for free later , we can not see the group name
	}


	// thow all transactions of this user
  elseif (ig('user') && (int) g('user') ) {
		$query['WHERE'] .= ' AND user = "' . g('user') . '"';
		$all_trnc_page_title = $olang['KJP_USR_PAYMNT'] . ' : ' . strtoupper( $UserById[ g('user') ] );
	}
	// if the buyer of the file is not member , we can bring his/her payments by IP .
  elseif (ig('ip') && (int) g('ip') ) {
		$query['WHERE'] .= ' AND payment_payer_ip = "' . g('ip') . '"';
		$all_trnc_page_title = $olang['KJP_IP_PAYMNT'] . ' : ' . g('ip');
	}
	// to check the payments that used this method
	elseif ( ig('method') ) {
		$query['WHERE'] .= ' AND payment_method = "' . g('method') . '"';
		$all_trnc_page_title = $olang['KJP_PAY_BY_MTHD'] . ' : ' . strtoupper( g('method') );
	}

	$all_result = $SQL->build($query);
	$have_transaction = flase ;

	if ( $num_rows = $SQL->num_rows($all_result)) 
	{
		// Pagination //

		$perpage	  	= 21;
		$currentPage	= ig('page') ? g('page', 'int') : 1;
		$Pager			  = new Pagination($perpage, $num_rows, $currentPage);
		$start			  = $Pager->getStartRow();
		$linkgoto     = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions';
		$linkgoto    .= ( ig('today') ? '&amp;today=1' : ( ig('file') ? '&amp;file=' . g('file') : ( ig('group') ? '&amp;group=' . g('group') : ( ig('thismonth') ? '&amp;thismonth=' . g('thismonth') : ( ig('user') ? '&amp;user=' . g('user') : ( ig('ip') ? '&amp;ip=' . g('ip') : ( ig('method') ? '&amp;method=' . g('method') : null ) ) ) ) ) ) );
		$page_nums		= $Pager->print_nums( $linkgoto );
		$query['LIMIT'] = "$start, $perpage";
		$all_result = $SQL->build($query);


		
		$have_transaction = true;
		$transactions = array();

		while ( $trnc = $SQL->fetch($all_result ) ) 
		{
			$PayID = $trnc['id'];
			$PayUser = $trnc['user'] > 0 ? $UserById[$trnc['user']] : $olang['KJP_GUEST'];
			$PayAction = $trnc['payment_action'] == 'buy_file' ? $olang['KJP_BYNG_FILE'] . ' : ' . $trnc['item_name'] 
			             : $olang['KJP_JUNG_GRP'] . ' : ' . $trnc['item_name'] ;
			$PayDateTime = $trnc['payment_year'] . '-' . $trnc['payment_month'] . '-' . $trnc['payment_day'] . '/' . $trnc['payment_time'] ;
	
			$transactions[] = array(
				'PayID' => $PayID ,
				'PayUser' => $PayUser ,
				'PayAction' => $PayAction ,
				'PayDateTime' => $PayDateTime ,
				'view_link' => basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . $PayID
			);
	
		}
	}


// view payment details .. 

}
elseif ( $current_smt == 'view' && (int) g('payment') ) 
{

	$stylee = 'view_payment';
	$PayInfo = getPaymentInfo( g('payment') , "payment_state = 'approved'" );

	if (! $PayInfo ) {
		$have_payment = false ;
	}
	else {
		$have_payment = true ;

		$id          = $PayInfo['id'];
		$amount      = $PayInfo['payment_amount'] . ' ' .$PayInfo['payment_currency'];
		$token       = $PayInfo['payment_token'];
		$payment_method	 = strtoupper($PayInfo['payment_method']);
		$payer_mail  = $PayInfo['payment_payer_mail'];
		$payer_ip    = $PayInfo['payment_payer_ip'];

		$item        =  $PayInfo['payment_action'] == 'buy_file' ? $olang['KJP_BYNG_FILE'] . ' : <a target="_blank" href="'. $config['siteurl'] .'do.php?id=' .$PayInfo['item_id']. '">' . $PayInfo['item_name'] . '</a>'
					 : $olang['KJP_JUNG_GRP'] . ' : <a target="_blank" href="' .basename(ADMIN_PATH). '?cp=g_users&smt=group_data&qg=' .$PayInfo['item_id']. '">'. $PayInfo['item_name'] . '</a>' ;
					 
		$member      = $PayInfo['user'] > 0 ? '<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=g_users&smt=edit_user&uid=' .$PayInfo['user']. '">'. $UserById[$PayInfo['user']] . '</a>'
		             : $olang['KJP_GUEST'];

		$date_time    = $PayInfo['payment_year'] . '-' . $PayInfo['payment_month'] . '-' . $PayInfo['payment_day'] . ' / ' . $PayInfo['payment_time'] ;
	
		$file_payments = $PayInfo['payment_action'] == 'buy_file' ? 
		'<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=kj_payment_options&smt=all_transactions&file=' .$PayInfo['item_id']. '">'.$olang['KJP_FILE_PAYMNT'] . ' : ' . $PayInfo['item_name'] .'</a>'
		: '<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=kj_payment_options&smt=all_transactions&group=' .$PayInfo['item_id']. '">'.$olang['KJP_GRP_PAYMNT'] .' : '. $PayInfo['item_name'] .'</a>' ;

		$user_payments = $PayInfo['user'] > 0 ? 
		'<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=kj_payment_options&smt=all_transactions&user=' .$PayInfo['user']. '">'. $olang['KJP_USR_PAYMNT'] . ' : ' . $UserById[$PayInfo['user']] .'</a>'
		: '<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=kj_payment_options&smt=all_transactions&ip=' .$payer_ip. '">'.$olang['KJP_IP_PAYMNT'] . ' : ' . $payer_ip .'</a>';
	
		$method_payments = '<a target="_blank" href="' .basename(ADMIN_PATH). '?cp=kj_payment_options&smt=all_transactions&method=' .$PayInfo['payment_method']. '">'. $olang['KJP_PAY_BY_MTHD'] . ' : ' . strtoupper($PayInfo['payment_method']) .'</a>';

		$viewMoreTable = array(); // evry method have some informations
		$methodPaymentInfo = array();

		$methodPaymentInfo['payment_more_info'] = $PayInfo['payment_more_info']; // we don't want to get all information again :: omly the method informations

		foreach (payment_more_info('from_db' , $methodPaymentInfo) as $key => $value) 
		{
			$viewMoreTable[] = array(
				'tableName'  => $olang['KJP_VIW_TPL_' . strtoupper($key)]  ,
				'tableValue' => $value
			);
		}

	}



	// set a price for file .
}
elseif ( $current_smt == 'pricing_file') 
{
	
	$stylee = 'add_price';
	

	if ( ip('open_file') ) {

		
		$select_file_id =  ip('select_file_id') ? p('select_file_id') : null  ; 

		$ExampleID = $config['siteurl'] . 'do.php?id=';
		$ExampleIMG = $config['siteurl'] . 'do.php?img=';

		! (int) $select_file_id ? $select_file_id = str_replace(array($ExampleID , $ExampleIMG) , '' , $select_file_id) : $select_file_id ;

		if ( $select_file_id !== null && $select_file_id > 0 && $file_info = getFileInfo($select_file_id)) 
		{
			

			$show_price_panel = true;
			

			$FileID = $file_info['id'];
			$FileName = $file_info['real_filename'];
			$FileSize = $file_info['size'];
			$FileUser = $file_info['user'] > 0 ? $UserById[$file_info['user']] : $olang['KJP_GUEST'];
			$FilePrice = $file_info['price'];

		}else {
			$OpenAlert = true;
			$AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] . $select_file_id;
			$AlertRole = 'danger';
		}


	}elseif ( ip('set_price') ) {

		$FileID = (int) p('price_file_id');
		$FileName = p('file_name') ;
		$FilePrice = p('price_file');

		if ( $file_info = getFileInfo( $FileID ) ) 
		{
			$update_query = array(
				'UPDATE' => $dbprefix . 'files' ,
				'SET'    => "price = '{$FilePrice}'" ,
				'WHERE'  => "id = '{$FileID}' AND real_filename = '{$FileName}'"
			);

			$SQL->build( $update_query );

			if ($SQL->affected()) {
				
				$OpenAlert = true;
				$AlertMsg = $olang['KJP_NO_FILE_NEW_PRICE'] .$FileName. " => ". $FilePrice . strtoupper($config['iso_currency_code']) ;
				$AlertRole = 'success';
			}else {
				$OpenAlert = true;
				$AlertMsg = $olang['KJP_NO_FILE_WITH_ID'] . $FileID;
				$AlertRole = 'danger';
			}
		}
	}

}
elseif ( $current_smt == 'paid_files') 
{

	$stylee = 'paid_files';

	$all_paid_file = array();

	$query = array(
					'SELECT' => 'id , real_filename , user , price' ,
					'FROM'   => "{$dbprefix}files" ,
					'WHERE'  => 'price > 0'
	);
	
	$paid_f = $SQL->build( $query );

	if (  $num_rows = $SQL->num_rows( $paid_f ) ) 
	{

				// Pagination //

				$perpage	  	= 21;
				$currentPage	= ig('page') ? g('page', 'int') : 1;
				$Pager			  = new Pagination($perpage, $num_rows, $currentPage);
				$start			  = $Pager->getStartRow();
				$linkgoto     = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=paid_files';
				$page_nums		= $Pager->print_nums( $linkgoto );
				$query['LIMIT'] = "$start, $perpage";
				$paid_f = $SQL->build($query);
		


		$have_paid_file = true;
		while ( $paid_file = $SQL->fetch($paid_f) ) 
		{
			$all_paid_file[] = array(
				'id' => $paid_file['id'] ,
				'name' => $paid_file['real_filename'] ,
				'user' => $paid_file['user'] > 0 ? $UserById[$paid_file['user']] : $olang['KJPP_GUEST'] ,
				'price' => $paid_file['price'] ,
				'link' => $config['siteurl'] . 'do.php?id=' . $paid_file['id']
			);
		}
	}
	
}
elseif ( $current_smt == 'archive' && ig('date') ) 
{
	
	$stylee = 'archive_data';

  $archive_panel_1   = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 
  $archive_panel_2_1 = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 
  $archive_panel_2_2 = array( /* 0 => array(  'methodName' => 'PayPal' , 'htmlContent' => '<h1> display this info </h1>'  ) */ ); 

	$Archive_data = get_archive( g('date') );


	// the panel of all transactions
	$archive_trnc_count = $Archive_data['all_trnc_num'];
	$archive_panel_1[] = array( 'methodName' => 'PayPal' , 'htmlContent' =>  $Archive_data['paypalArchive']['all']['num'] );
	$archive_panel_1[] = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $Archive_data['paypalArchive']['all']['amount'] . ' '. strtoupper($config['iso_currency_code']) );
	$archive_panel_1[] = array( 'methodName' => 'Stripe' , 'htmlContent' =>  $Archive_data['cardsArchive']['all']['num'] );
	$archive_panel_1[] = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $Archive_data['cardsArchive']['all']['amount'] . ' '. strtoupper($config['iso_currency_code']) );


	// the panel of files transactions
	$archive_file_trnc  = $Archive_data['file_trnc_num'];
	$archive_panel_2_1[] = array( 'methodName' => 'PayPal' , 'htmlContent' => ' ' . $Archive_data['paypalArchive']['file']['num'] );
	$archive_panel_2_1[] = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $Archive_data['paypalArchive']['file']['amount'] . ' '. strtoupper($config['iso_currency_code']) );
	$archive_panel_2_1[] = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $Archive_data['cardsArchive']['file']['num'] );
	$archive_panel_2_1[] = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $Archive_data['cardsArchive']['file']['amount'] . ' '. strtoupper($config['iso_currency_code']) );


 // the panel of joining groups transactions
	$archive_group_trnc = $Archive_data['group_trnc_num'];
	$archive_panel_2_2[] = array( 'methodName' => 'PayPal' , 'htmlContent' => ' ' . $Archive_data['paypalArchive']['group']['num'] );
	$archive_panel_2_2[] = array( 'methodName' => 'PayPal' , 'htmlContent' => $olang['KJP_NT_PRFIT'] . ' : ' . $Archive_data['paypalArchive']['group']['amount'] . ' '. strtoupper($config['iso_currency_code']) );
	$archive_panel_2_2[] = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $Archive_data['cardsArchive']['group']['num'] );
	$archive_panel_2_2[] = array( 'methodName' => 'Stripe' , 'htmlContent' => ' ' . $Archive_data['cardsArchive']['group']['amount'] . ' '. strtoupper($config['iso_currency_code']) );




	$query = $Archive_data['query'];

	$query['SELECT'] .= ', id , payment_state , payment_payer_ip , item_id , item_name , user , payment_time';
	$query['ORDER BY'] = 'id DESC';


	$archive_payments = $SQL->build($query);
    $ArchivePayNum = false;

	if ( $num_rows = $SQL->num_rows($archive_payments)) {

				// Pagination //

				$perpage	  	= 21;
				$currentPage	= ig('page') ? g('page', 'int') : 1;
				$Pager			  = new Pagination($perpage, $num_rows, $currentPage);
				$start			  = $Pager->getStartRow();
				$linkgoto     = basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=archive&date='.g('date');
				$page_nums		= $Pager->print_nums( $linkgoto );
				$query['LIMIT'] = "$start, $perpage";
				$archive_payments = $SQL->build($query);

		$ArchivePay = array();
		$ArchivePayNum = true;
	
		while ($rows = $SQL->fetch($archive_payments) ) 
		{
	
			$PayID = $rows['id'];
			$PayUser = $rows['user'] > 0 ? $UserById[$rows['user']] : $olang['KJP_GUEST'];
			$PayAction = $rows['payment_action'] == 'buy_file' ? $olang['KJP_BYNG_FILE'] . ' : ' . $rows['item_name'] : $olang['KJP_JUNG_GRP'] . ' : ' . $rows['item_name'];
			$PayIP = $rows['payment_payer_ip'];
			$PayDateTime = $rows['payment_year'] . '-' . $rows['payment_month'] . '-' . $rows['payment_day'] . '/' . $rows['payment_time'] ;
	
			$ArchivePay[] = array(
				'PayID' => $PayID ,
				'PayUser' => $PayUser ,
				'PayAction' => $PayAction ,
				'PayIP' => $PayIP ,
				'PayDateTime' => $PayDateTime ,
				'view_link' => basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=view&amp;payment=' . $PayID
			);
	
		}

	}

}
elseif ( $current_smt == 'help') 
{
	$stylee = 'help';

}

$go_menu = array(
				'pricing_file' => array('name'=> $olang['KJP_PRC_FILE'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=pricing_file', 'goto'=>'pricing_file', 'current'=> $current_smt == 'pricing_file'),
				'paid_files' => array('name'=> $olang['KJP_PAID_FILE'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=paid_files', 'goto'=>'paid_files', 'current'=> $current_smt == 'paid_files'),
				'all_transactions' => array('name'=> $olang['KJP_ALL_TRNC'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=all_transactions', 'goto'=>'all_transactions', 'current'=> $current_smt == 'all_transactions'),
				'help' => array('name'=> $olang['KJP_HLP'], 'link'=> basename(ADMIN_PATH) . '?cp=kj_payment_options&amp;smt=help', 'goto'=>'help', 'current'=> $current_smt == 'help'),
	);
