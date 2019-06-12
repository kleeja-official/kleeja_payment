<?php



function getFileInfo($fileID = 0, $getInfo = '*')
{
    if (! (int) $fileID && ! $fileID > 0)
    {
        return false;
    }

    global $SQL , $dbprefix;

    $query = [
        'SELECT' => $getInfo ,
        'FROM'   => $dbprefix . 'files' ,
        'WHERE'  => 'id = ' . $fileID ,
        'LIMIT'  => '1'
    ];

    $result = $SQL->build($query);

    if ($SQL->num_rows($result))
    {
        return $SQL->fetch_array($result);
    }
    else
    {
        return false;
    }
}



function getPaymentInfo($db_id = 0, $where = '', $mixall = false)
{
    global $SQL , $dbprefix;

    $query    = [
        'SELECT'   => '*',
        'FROM'     => "{$dbprefix}payments",
        'WHERE'    => 'id = ' . $db_id ,
        'LIMIT'    => '1',
    ];

    if ($where !== '')
    {
        $query['WHERE'] .= ' AND ' . $where;
    }

    $result    = $SQL->build($query);

    if ($SQL->num_rows($result))
    {
        $result = $SQL->fetch_array($result);

        if ($mixall)
        {
            $result = payment_more_info('from_db', $result);
        }

        return $result;
    }
    else
    {
        return false;
    }
}


function getPayoutInfo($db_id = 0, $where = '', $mixall = false)
{
    global $SQL , $dbprefix;

    $query    = [
        'SELECT'   => '*',
        'FROM'     => "{$dbprefix}payments_out",
        'WHERE'    => 'id = ' . $db_id ,
        'LIMIT'    => '1',
    ];

    if ($where !== '')
    {
        $query['WHERE'] .= ' AND ' . $where;
    }

    $result    = $SQL->build($query);

    if ($SQL->num_rows($result))
    {
        $result = $SQL->fetch_array($result);

        if ($mixall)
        {
            $result = payment_more_info('from_db', $result);
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
function getGroupInfo($groupData, $getGroup = 'all')
{
    $return = [];

    foreach ($groupData as $data)
    {
        if ($data['data']['group_id'] > 3 // not the default groups
        && $data['configs']['join_price'] > 0
        && $data['data']['group_is_default'] == 0)
        { // maybe the webmaster using another group as default registration group ..
            if ($getGroup == 'all')
            {
                $group_id         = $data['data']['group_id'];
                $group_name       = $data['data']['group_name'];
                $join_price       = $data['configs']['join_price'];
                $min_payout_limit = $data['configs']['min_payout_limit'];


                $return[] = [
                    'id'               => $group_id ,
                    'name'             => $group_name ,
                    'price'            => $join_price ,
                    'min_payout_limit' => $min_payout_limit ,
                ];
            }
            elseif ((int) $getGroup
            && $getGroup > 3
            && $data['data']['group_id'] == $getGroup
            && $data['data']['group_is_default'] == 0)
            {
                $group_id         = $data['data']['group_id'];
                $group_name       = $data['data']['group_name'];
                $join_price       = $data['configs']['join_price'];
                $min_payout_limit = $data['configs']['min_payout_limit'];


                $return = [
                    'id'               => $group_id ,
                    'name'             => $group_name ,
                    'price'            => $join_price ,
                    'min_payout_limit' => $min_payout_limit ,
                ];
            }
        }
    }

    if (count($return) === 0)
    {
        return false;
    }


    return $return;
}

function is_style_supported()
{
    global $config;

    // 'kleeja_paypal' is made with bootstrap , other styles have to make their pages to use this plugin

    $supported_styles = ['dragdrop' , 'bootstrap_black' , 'bootstrap'];

    return in_array($config['style'], $supported_styles) ? true : false;
}



/*
 * MySql sum function was making some problems with float strings
 * so this is another way to collect the final result for kj_payment control panel
 * this function is used only in kj_payment_options.php
 */

function KJPayFinalData()
{
    global $SQL , $dbprefix;



    $all_trnc_num                      = 0; // the number of all transactions .
        $today_trnc_num                = 0; // the number of daily transactions
        $ThisMonth_trnc_num            = 0; // the number of transactions in this month

        $paypalTransactions = [
            'all' => [
                'num' => 0 , 'amount' => 0
            ] ,
            'monthly' => [
                'num' => 0 , 'amount' => 0
            ] ,
            'daily' => [
                'num' => 0 , 'amount' => 0
            ]
        ];

    $cardsTransactions  = [
        'all' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'monthly' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'daily' => [
            'num' => 0 , 'amount' => 0
        ]
    ];
    $balanceTransactions  = [
        'all' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'monthly' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'daily' => [
            'num' => 0 , 'amount' => 0
        ]
    ];





    $result = $SQL->query("SELECT id , payment_amount , payment_method, payment_more_info ,payment_year , payment_month , payment_day FROM {$dbprefix}payments WHERE payment_state = 'approved'");

    while ($row = $SQL->fetch($result))
    {
        $row = payment_more_info('from_db', $row);
        // this is for all
        $all_trnc_num++;

        // paypal method informations
        if ($row['payment_method'] == 'paypal')
        {
            $paypalTransactions['all']['num']++;
            $paypalTransactions['all']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);
        }
        // cards method informations
        elseif ($row['payment_method'] == 'cards')
        {
            $cardsTransactions['all']['num']++;
            $cardsTransactions['all']['amount'] += $row['payment_amount'];
        }
        // balance method informations
        elseif ($row['payment_method'] == 'balance')
        {
            $balanceTransactions['all']['num']++;
            $balanceTransactions['all']['amount'] += $row['payment_amount'];
        }


        // count of daily transactions
        if (
                    $row['payment_year'] == date('Y') &&
                    $row['payment_month'] == date('m') &&
                    $row['payment_day'] == date('d')
                ) {
            $today_trnc_num++;
            // paypal method informations
            if ($row['payment_method'] == 'paypal')
            {
                $paypalTransactions['daily']['num']++;
                $paypalTransactions['daily']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);
            }
            // cards method informations
            elseif ($row['payment_method'] == 'cards')
            {
                $cardsTransactions['daily']['num']++;
                $cardsTransactions['daily']['amount'] += $row['payment_amount'];
            }
            // cards method informations
            elseif ($row['payment_method'] == 'balance')
            {
                $balanceTransactions['daily']['num']++;
                $balanceTransactions['daily']['amount'] += $row['payment_amount'];
            }
        }

        // count of monthly transactions

        if (
                    $row['payment_year'] == date('Y') &&
                    $row['payment_month'] == date('m')
                ) {
            $ThisMonth_trnc_num++;
            // paypal method informations
            if ($row['payment_method'] == 'paypal')
            {
                $paypalTransactions['monthly']['num']++;
                $paypalTransactions['monthly']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);
            }
            // cards method informations
            elseif ($row['payment_method'] == 'cards')
            {
                $cardsTransactions['monthly']['num']++;
                $cardsTransactions['monthly']['amount'] += $row['payment_amount'];
            }
            // balance method informations
            elseif ($row['payment_method'] == 'balance')
            {
                $balanceTransactions['monthly']['num']++;
                $balanceTransactions['monthly']['amount'] += $row['payment_amount'];
            }
        }
    }

    return [
        'kj_payments' => ['all' => $all_trnc_num , 'monthly' => $ThisMonth_trnc_num , 'daily' => $today_trnc_num] ,

        // payment informations that made by paypal
        'paypal'      => $paypalTransactions ,
        // payment informations that made by cards
        'balance'     => $balanceTransactions ,
        // payment informations that made by cards
        'cards'      => $cardsTransactions
    ];
}


/**
 * all pages in kj_payment_option.php need to get user by id from the database ,
 * so we will bring all data only one time by this function .
 */

function UserById()
{
    global $SQL , $dbprefix;
    $return   = [];
    $all_user = $SQL->query("SELECT id , name FROM {$dbprefix}users");

    while ($user = $SQL->fetch($all_user))
    {
        $return[$user['id']] = $user['name'];
    }

    return $return;
}



function get_archive ($date = '30-2-yyyy')
{
    global $SQL , $dbprefix;

    $date = explode('-', $date);


    $query = [
        'SELECT' => 'payment_action , payment_method , payment_more_info , payment_amount ,payment_year , payment_month , payment_day' ,
        'FROM'   => $dbprefix . 'payments' ,
        'WHERE'  => "payment_state = 'approved' AND "
    ];


    if (count($date) == 2)
    {
        // Monthly Archive EX: mm-yyyy | note : i did not had problem if the day or month was ( 01 ) or ( 1 ) , mysql accespted it
        $date = [
            'year'  => $date[1] ,
            'month' => $date[0] ,
        ];

        $query['WHERE'] .= "payment_year = '" . $date['year'] . "' AND payment_month = '" . $date['month'] . "'";
    }
    elseif (count($date) == 3)
    {
        // Daily Archive EX: dd-mm-yyyy
        $date = [
            'year'  => $date[2] ,
            'month' => $date[1] ,
            'day'   => $date[0]
        ];

        $query['WHERE'] .= "payment_year = '" . $date['year'] . "' AND payment_month = '" . $date['month'] . "' AND payment_day = '" . $date['day'] . "'";
    }

    $archive_result = $SQL->build($query);

    $all_trnc_num                 = 0; // the number of all transactions .
    $file_trnc_num                = 0; // the number of all transaction of buying files
    $group_trnc_num               = 0; // the number of all transaction og joining groups

    $paypalArchive = [
        'all' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'file' => [
            'num' => 0 , 'amount' => 0
        ] ,
        'group' => [
            'num' => 0 , 'amount' => 0
        ]
    ];

    $cardsArchive = [
        'all' => [
            'num' => 0 , 'amount' => 0
        ],
        'file' => [
            'num' => 0 , 'amount' => 0
        ],
        'group' => [
            'num' => 0 , 'amount' => 0
        ],
    ];

    $balanceArchive = [
        'all' => [
            'num' => 0 , 'amount' => 0
        ],
        'file' => [
            'num' => 0 , 'amount' => 0
        ],
        'group' => [
            'num' => 0 , 'amount' => 0
        ],
    ];




    while ($row = $SQL->fetch($archive_result))
    {
        $row = payment_more_info('from_db', $row);

        $all_trnc_num++;

        if ($row['payment_method'] == 'paypal')
        {
            $paypalArchive['all']['num']++;
            $paypalArchive['all']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);

            if ($row['payment_action'] == 'buy_file')
            {
                $paypalArchive['file']['num']++;
                $paypalArchive['file']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);
            }
            else
            {
                $paypalArchive['group']['num']++;
                $paypalArchive['group']['amount'] += ($row['payment_amount'] - $row['paypal_payment_fees']);
            }
        }
        elseif ($row['payment_method'] == 'cards')
        {
            $cardsArchive['all']['num']++;
            $cardsArchive['all']['amount'] += $row['payment_amount'];

            if ($row['payment_action'] == 'buy_file')
            {
                $cardsArchive['file']['num']++;
                $cardsArchive['file']['amount'] += $row['payment_amount'];
            }
            else
            {
                $cardsArchive['group']['num']++;
                $cardsArchive['group']['amount'] += $row['payment_amount'];
            }
        }
        elseif ($row['payment_method'] == 'balance')
        {
            $balanceArchive['all']['num']++;
            $balanceArchive['all']['amount'] += $row['payment_amount'];

            if ($row['payment_action'] == 'buy_file')
            {
                $balanceArchive['file']['num']++;
                $balanceArchive['file']['amount'] += $row['payment_amount'];
            }
            else
            {
                $balanceArchive['group']['num']++;
                $balanceArchive['group']['amount'] += $row['payment_amount'];
            }
        }

        $row['payment_action'] == 'buy_file' ? $file_trnc_num++ : $group_trnc_num++;
    }



    return [
        'query'               => $query , // we dont want to write it again , we will add some change and evrybody is happy .
        'all_trnc_num'        => $all_trnc_num ,
        'file_trnc_num'       => $file_trnc_num ,
        'group_trnc_num'      => $group_trnc_num ,
        'paypalArchive'       => $paypalArchive ,
        'cardsArchive'        => $cardsArchive ,
        'balanceArchive'      => $balanceArchive ,
        'date'                => $date
    ];
}

function getPaymentMethods()
{
    global $SQL , $dbprefix;

    $get_methods = $SQL->query("SELECT `name` FROM {$dbprefix}config WHERE `value` = '1' AND `type` = 'kj_pay_active_mthd'");

    $return = [];

    if ($SQL->num_rows($get_methods))
    {
        while ($methods = $SQL->fetch($get_methods))
        {
            if (! user_can('recaive_profits') && $methods['name'] == 'active_balance')
            {
                continue;
            }
            $return[] = str_replace('active_', '', $methods['name']);
        }
    }

    return $return;
}



function createToken($length = 16)
{
    $ALPHA   = 'abcdefghijklmnopqrstvwxyz';
    $NUMERIC = '0123456789';

    $chars = $NUMERIC . $ALPHA . strtoupper($ALPHA);

    $token = '';

    for ($i = 0; $i < $length; $i++)
    {
        $token .= $chars[mt_rand(0, strlen($chars) - 1)];
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
     * @param mixed $action
     * @param mixed $data
     */

 function payment_more_info($action, $data = [])
 {
     if ($action == 'to_db')
     {
         $return = [];

         foreach ($data as $key => $value)
         {
             $return[] = "{$key}->{$value}";
         }

         $return = implode('::', $return);
     }
     elseif ($action == 'from_db')
     {
         $return = [];
         // make a string to an array
         $data['payment_more_info'] = explode('::', $data['payment_more_info']);

         foreach ($data['payment_more_info'] as $value)
         {
             $value           = explode('->', $value);
             $data[$value[0]] = $value[1];
         }

         // we dont need it anymore
         unset($data['payment_more_info']);

         // now all the data mixed to make one array
         $return = $data;
     }

     return $return;
 }
