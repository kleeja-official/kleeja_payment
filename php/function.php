<?php



function getFileInfo( $fileID = 0 , $getInfo = '*')
{
    if ( ! (int) $fileID && ! $fileID > 0) 
    {
        return false ;
    }

    global $SQL , $dbprefix ;

    $query = array(
        'SELECT' => $getInfo ,
        'FROM' => $dbprefix . 'files' ,
        'WHERE' => 'id = ' . $fileID ,
        'LIMIT' => '1'
    );

    $result	= $SQL->build($query);

    if ($SQL->num_rows($result))
    {
        return $SQL->fetch_array($result);
    }
    else 
    {
        return false;
    }



}



function getPaymentInfo( $db_id = 0 , $where = '' ,$mixall = false )
{
    global $SQL , $dbprefix;

    $query	= array(
        'SELECT'=> '*',
        'FROM'	=> "{$dbprefix}payments",
        'WHERE'	=> "id = " . $db_id ,
        'LIMIT'	=> '1',
    );

    if ( $where !== '' ) 
    {
        $query['WHERE'] .= ' AND ' . $where ;
    }

    $result	= $SQL->build($query);

    if ( $SQL->num_rows($result) )
    {
        $result = $SQL->fetch_array( $result );
        if ($mixall) 
        {
            $result = payment_more_info('from_db' , $result);
        }

        return $result;
    }
    else 
    {
        return false;
    }


}
/*
 * $groupDtData = $args['d_groups'];
 * $getGroup = 'all' or 'the id of group'
 */
function getGroupInfo($groupData , $getGroup = 'all') 
{



    $return = array();

    foreach ($groupData as $data) 
    {

        if ( $data['data']['group_id'] > 3 # not the default groups
        && $data['configs']['join_price'] > 0 
        && $data['data']['group_is_default'] == 0) # maybe the webmaster using another group as default registration group ..
        {
           
            if ($getGroup == 'all') {
                
                $group_id = $data['data']['group_id'];
                $group_name = $data['data']['group_name'];
                $join_price = $data['configs']['join_price'];

        
                $return[] = array(
                    'id' => $group_id , 
                    'name' => $group_name , 
                    'price' => $join_price ,
                );
            }
            elseif ((int) $getGroup 
            && $getGroup > 3 
            && $data['data']['group_id'] == $getGroup 
            && $data['data']['group_is_default'] == 0 ) 
            {
                
                $group_id = $data['data']['group_id'];
                $group_name = $data['data']['group_name'];
                $join_price = $data['configs']['join_price'];

        
                $return = array(
                    'id' => $group_id , 
                    'name' => $group_name , 
                    'price' => $join_price ,
                );
            }

        }
    }

    if (count($return) === 0) 
    {
        return false;
    }


    return $return;

}

function is_style_supported(){

    global $config;

    // 'kleeja_paypal' is made with bootstrap , other styles have to make their pages to use this plugin

    $supported_styles = array( 'dragdrop' , 'bootstrap_black' , 'bootstrap'); 
    
    return in_array( $config['style'] , $supported_styles) ? true : false ; 
    
}



/*
 * MySql sum function was making some problems with float strings
 * return array(
     'all' => array('number' => 14 , 'amount' => 7777 , 'fees' => 77)
     'today' => array('number' => 7 , 'amount' => 777 , 'fees' => 7)
     'month' => array('number' => 7 , 'amount' => 777 , 'fees' => 7)
 );

 */

function paypal_sum_trnc()
{
    global $SQL , $dbprefix ;


        
        $all_trnc_num                  = 0; // the number of all transactions .
        $all_trnc_amount               = 0; // the number of all transactions amounts
        $all_trnc_fees                 = 0; // the number of all transactions fees
        $paypal_all_trnc_count         = 0; // the number of all transactions that made by paypal
        $today_trnc_num                = 0; // the number of daily transactions
        $today_trnc_amount             = 0; // daily transactions amounts
        $today_trnc_fees               = 0; // daily transactions fees 
        $paypal_today_trnc_count       = 0; // the number of daily transactions that made by paypal
        $ThisMonth_trnc_num            = 0; // the number of transactions in this month
        $ThisMonth_trnc_amount         = 0; // transactions amounts of this month
        $ThisMonth_trnc_fees           = 0; // transactions fees of this month 
        $paypal_ThisMonth_trnc_count   = 0; // the number of monthly transactions that made by paypal
    
    
    
        $result = $SQL->query("SELECT id , payment_amount , payment_method, payment_more_info ,payment_year , payment_month , payment_day FROM {$dbprefix}payments WHERE payment_state = 'approved'");
    
        while ($row = $SQL->fetch($result)) 
        {
            $row = payment_more_info('from_db' , $row);
            // this is for all
            $all_trnc_num++; 

            // this is for paypal only -> other method have different ways :)
            if ( $row['payment_method'] === 'paypal' ) 
            {
                $all_trnc_amount = $all_trnc_amount + $row['payment_amount'];
                $all_trnc_fees = $all_trnc_fees + $row['paypal_payment_fees'];
                $paypal_all_trnc_count++;
                if (
                    $row['payment_year'] == date('Y') &&
                    $row['payment_month'] == date('m') &&
                    $row['payment_day'] == date('d')
                ) {
                    $today_trnc_num++;
                    $today_trnc_amount = $today_trnc_amount + $row['payment_amount'];
                    $today_trnc_fees = $today_trnc_fees + $row['paypal_payment_fees'];
                    $paypal_today_trnc_count++;
                }
                if (
                    $row['payment_year'] == date('Y') &&
                    $row['payment_month'] == date('m')
                ) {
                    $ThisMonth_trnc_num++;
                    $ThisMonth_trnc_amount = $ThisMonth_trnc_amount + $row['payment_amount'];
                    $ThisMonth_trnc_fees = $ThisMonth_trnc_fees + $row['paypal_payment_fees'];
                    $paypal_ThisMonth_trnc_count++;
                }
            }
            
        }

        return array(
            'all'   => array('number' => $all_trnc_num , 'amount' => $all_trnc_amount , 'fees' => $all_trnc_fees , 'paypal' => $paypal_all_trnc_count) ,
            'today' => array('number' => $today_trnc_num , 'amount' => $today_trnc_amount , 'fees' => $today_trnc_fees , 'paypal' => $paypal_today_trnc_count) ,
            'month' => array('number' => $ThisMonth_trnc_num , 'amount' => $ThisMonth_trnc_amount , 'fees' => $ThisMonth_trnc_fees , 'paypal' => $paypal_ThisMonth_trnc_count)
        );

}


/**
 * all pages in kj_payment_option.php need to get user by id from the database ,
 * so we will bring all data only one time by this function .
 */

function UserById()
{
    global $SQL , $dbprefix;
    $return = array();
    $all_user = $SQL->query("SELECT id , name FROM {$dbprefix}users");

    while ( $user = $SQL->fetch($all_user) ) 
    {
        $return[$user['id']] = $user['name'];
    }

    return $return;
}



function get_archive ($date = '30-2-yyyy')
{
    global $SQL , $dbprefix ;

    $date = explode('-' ,$date);


    $query = array(
        'SELECT' => 'payment_action , payment_method , payment_more_info , payment_amount ,payment_year , payment_month , payment_day' ,
        'FROM'   => $dbprefix.'payments' ,
        'WHERE'  => "payment_state = 'approved' AND "
    );


    if (count($date) == 2) 
    {
        // Monthly Archive EX: mm-yyyy | note : i did not had problem if the day or month was ( 01 ) or ( 1 ) , mysql accespted it
        $date = array(
            'year' => $date[1] ,
            'month' => $date[0] ,
        );

        $query['WHERE'] .= "payment_year = '" .$date['year']. "' AND payment_month = '" .$date['month'] . "'";
    
    }elseif (count($date) == 3) 
    {
        // Daily Archive EX: dd-mm-yyyy
        $date = array(
            'year' => $date[2] ,
            'month' => $date[1] ,
            'day' => $date[0]
        );

        $query['WHERE'] .= "payment_year = '" .$date['year']. "' AND payment_month = '" .$date['month']. "' AND payment_day = '" .$date['day'] . "'";
    
    }

    $archive_result = $SQL->build($query);

    $all_trnc_num                 = 0; // the number of all transactions .
    $paypal_all_trnc_num          = 0; // the number of all transactions . made by paypal
    $all_trnc_amount              = 0; // the number of all transactions amounts
    $all_trnc_fees                = 0; // the number of all transactions fees
    $trnc_of_files                = 0; // Files Transactions
    $paypal_trnc_of_files         = 0; // Files Transactions made by paypal
    $paypal_trnc_of_files_profit  = 0; // Files Transactions made by paypal
    $trnc_of_groups               = 0; // Transactions of Joining Groups
    $paypal_trnc_of_groups        = 0; // Transactions of Joining Groups made by paypal
    $paypal_trnc_of_groups_profit = 0; // Transactions of Joining Groups made by paypal


    while ($row = $SQL->fetch($archive_result)) 
    {
        $row = payment_more_info('from_db' , $row);
        
        $all_trnc_num++; 

        if ($row['payment_method'] == 'paypal') 
        {
            // Exclusive for paypal method
            $paypal_all_trnc_num++;
            $all_trnc_amount = $all_trnc_amount + $row['payment_amount'];
            $all_trnc_fees = $all_trnc_fees + $row['paypal_payment_fees'];  
            $row['payment_action'] == 'buy_file' ? $paypal_trnc_of_files++ : $paypal_trnc_of_groups++ ; 

            $row['payment_action'] == 'buy_file' ?
             $paypal_trnc_of_files_profit   += ( $row['payment_amount'] - $row['paypal_payment_fees'] ) :
              $paypal_trnc_of_groups_profit += ( $row['payment_amount'] - $row['paypal_payment_fees'] );  

        }

        $row['payment_action'] == 'buy_file' ? $trnc_of_files++ : $trnc_of_groups++ ;

    }



    return array(
        'query'               => $query , // we dont want to write it again , we will add some change and evrybody is happy .
        'number'              => $all_trnc_num ,
        'paypal_number'       => $paypal_all_trnc_num ,
        'amount'              => $all_trnc_amount ,
        'fees'                => $all_trnc_fees ,
        'file_trnc'           => $trnc_of_files ,
        'group_trnc'          => $trnc_of_groups,
        'paypal_file_trnc'    => $paypal_trnc_of_files ,
        'paypal_file_profit'  => $paypal_trnc_of_files_profit ,
        'paypal_group_trnc'   => $paypal_trnc_of_groups,
        'paypal_group_profit' => $paypal_trnc_of_groups_profit,
    );



}

function getPaymentMethods()
{
    global $SQL , $dbprefix;

    $get_methods = $SQL->query("SELECT `name` FROM {$dbprefix}config WHERE `value` = '1' AND `type` = 'kj_pay_active_mthd'");

    $return = array();

    if ($SQL->num_rows($get_methods)) 
    {
        while ( $methods = $SQL->fetch( $get_methods ) ) 
        {
            $return[] = str_replace('active_' , '' , $methods['name']);
        }
    }

    return $return;
}



function createToken( $length = 16)
{
    $ALPHA = 'abcdefghijklmnopqrstvwxyz';
    $NUMERIC = '0123456789';

    $chars .= $NUMERIC . $ALPHA . strtoupper( $ALPHA );

    $token = '';
    for ( $i = 0; $i < $length; $i++ )
    {
        $token .= $chars[mt_rand( 0, strlen( $chars ) - 1 )];
    }
    return $token;
}


    /**
     * we will make an array in $this->createPayment() , and convert it to string by implode() function
     * becuse ( payer name and payer mail ....) is not supported with all methods
     * and when we need this informations , we will give all informations in the database to this function
     * and the function will mix all information to make one array
     * i hope , it is clear
     * $action = to_db or from_db
     * to_db   = we will convert an array to a string 
     * from_db = we will convert a string to an array
     * when $action = to_db the function @return a string
     * and when $action = from_db the function @return an array
     */

 function payment_more_info($action , $data = array())
     {
         if ($action == 'to_db') 
         {
             $return = array();
             foreach ($data as $key => $value) 
             {
                 $return[] = "{$key}->{$value}";
             }
 
             $return = implode('::' , $return);
 
         }
         elseif ($action == 'from_db') 
         {
             $return = array();
             // make a string to an array
             $data['payment_more_info'] = explode('::' , $data['payment_more_info']);
 
             foreach ($data['payment_more_info'] as $value) 
             {
                 
                 $value = explode('->' , $value);
                 $data[$value[0]] = $value[1];
             }
 
             // we dont need it anymore
             unset($data['payment_more_info']);
 
             // now all the data mixed to make one array
             $return = $data;
         }
 
         return $return;
     }
 
