<?php

class kjPayMethod_balance implements KJPaymentMethod
{
    private $currency;
    private $successPayment     = false; // its return the payment state after checking it
    private $varsForCreate      = []; // some methods will work in kleeja without leaving the website
    private $toGlobal           = []; // the list of vars that we want to export it to kleeja
    private $downloadLinkMailer = false; // the mail that we want to send download link to it 

    public function paymentStart()
    {
        global $lang , $config;

        if (! user_can('recaive_profits'))
        {
            /**
             * this will check for permission
             * and also it will check if user is login or not .
             * anyway , the Guest don't have this permission
             * if the user have this permission , that mean it's able for hem to use the balance
             */
            kleeja_err($lang['USER_PLACE']);

            exit;
        }
        elseif (! in_array('balance', getPaymentMethods()))
        {
            kleeja_err('it\'s not active method');

            exit;
        }
    }

    public function setCurrency($currency)
    {
        // it's not important , but ... it's the InterFace
        $this->currency = $currency;
    }

    public function CreatePayment($do, $info)
    {
        global $config , $olang ,$THIS_STYLE_PATH_ABS;


        $_SESSION['kj_payment'] =
        [
            'payment_action'    => $do ,
            'item_id'           => g('id') ,
            'item_name'         => $info['name'] ,
        ];

        $kjFormKeyGet  = kleeja_add_form_key_get('payFor_' . $do . $info['name'] . $info['id']);
        $kjFormKeyPost = kleeja_add_form_key('payFor_' . $do . $info['name'] . $info['id']);

        $this->varsForCreate['no_request']      = false; 
        $this->varsForCreate['titlee']          = 'Pay By Balance'; 
        $this->varsForCreate['stylee']          = 'pay_balance';
        $this->varsForCreate['styleePath']      = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/pay_balance.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment/' : dirname(__FILE__) . '/../html/';
        $this->varsForCreate['FormAction']      = $config['siteurl'] . 'go.php?go=kj_payment&method=balance&action=check&' . $kjFormKeyGet;
        $this->varsForCreate['itemName']        = $info['name'];
        $this->varsForCreate['payAction']       = sprintf($olang['KJP_ACT_' . strtoupper($do)], $info['name']);
        $this->varsForCreate['paymentCurrency'] = $this->currency;
        $this->varsForCreate['itemPrice']       = $info['price'] . ' ' . $this->currency;
        $this->varsForCreate['kjFormKeyPost']   = $kjFormKeyPost;
    }

    public function varsForCreatePayment()
    {
        return $this->varsForCreate;
    }


    public function checkPayment()
    {
        global $config , $usrcp , $SQL , $dbprefix , $d_groups ,$userinfo , $lang , $olang;

        if (! $usrcp->name())
        {
            // to be sure 100% , thats we are on the right way
            kleeja_err($lang['USER_PLACE'], '', true, $config['siteurl']);
            exit;
        }
        // is he comming from our page
        elseif (! isset($_SESSION['kj_payment']) || empty($_SESSION['kj_payment']))
        {
            kleeja_err('What Are U Doing Here ??', '', true, $config['siteurl']);

            exit;
        }
        // really from our page
        elseif (! kleeja_check_form_key('payFor_' . $_SESSION['kj_payment']['payment_action'] . $_SESSION['kj_payment']['item_name'] . $_SESSION['kj_payment']['item_id'])
        || ! kleeja_check_form_key_get('payFor_' . $_SESSION['kj_payment']['payment_action'] . $_SESSION['kj_payment']['item_name'] . $_SESSION['kj_payment']['item_id']))
        {
            kleeja_err($lang['INVALID_FORM_KEY']);

            exit;
        }
        // really really , check if the item is exists
        elseif (($_SESSION['kj_payment']['payment_action'] == 'buy_file') && ! $itemInfo = getFileInfo($_SESSION['kj_payment']['item_id']))
        {
            kleeja_err($olang['KJP_FL_NT_FUND']);

            exit;
        }
        elseif (($_SESSION['kj_payment']['payment_action'] == 'join_group') && ! $itemInfo = getGroupInfo($d_groups, $_SESSION['kj_payment']['item_id']))
        {
            kleeja_err($olang['KJP_GP_NT_FUND']);

            exit;
        }
        //export here $itemInfo
        is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:itemInfoExport_' . $_SESSION['kj_payment']['payment_action'], get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        // no Error , let's check if the user have this amount in hes balance or not
        $itemPrice = $itemInfo['price'];

        if ($itemPrice <= 0)
        {
            // this is free item
            kleeja_err($olang['KJP_FRE_ITM']);
        }
        //get freash user balance
        $userBalance = (float) $usrcp->get_data('balance')['balance'];

        if ($itemPrice > $userBalance)
        {
            // son , collect some money , then come to buy
            kleeja_err($olang['KJP_NO_BLNC'], '', true, $config['siteurl']);

            exit;
        }

        // i will take the money from you , then i will give you the item loooool
        $userNewBalance = (float) ($userBalance - $itemPrice);

        $SQL->query("UPDATE `{$dbprefix}users` SET `balance` = {$userNewBalance} WHERE `id` = {$userinfo['id']} AND `name` = '{$userinfo['name']}'");
        // The money is token now , so this item is HALAL for you Now
        // insert to the DataBase
        $payment_method    = 'balance';
        $payment_state     = 'approved';
        $payment_currency  = $this->currency;
        $payment_action    = $_SESSION['kj_payment']['payment_action'];
        $payment_token     = createToken();
        $payment_amount    = $itemInfo['price'];
        $payment_payer_ip  = get_ip();
        $item_id           = $_SESSION['kj_payment']['item_id'];
        $item_name         = $_SESSION['kj_payment']['item_name'];
        $user              = $usrcp->id();
        $payment_year      = date('Y');
        $payment_month     = date('m');
        $payment_day       = date('d');
        $payment_time      = date('H:i:s');

        $insert_query    = [
            'INSERT'      => 'payment_state , payment_method , payment_amount , payment_currency , payment_token , payment_payer_ip , payment_action , item_id , item_name , user , payment_year , payment_month , payment_day , payment_time',
            'INTO'        => "{$dbprefix}payments",
            'VALUES'      => "'$payment_state', '$payment_method' , '$payment_amount', '$payment_currency','$payment_token', '$payment_payer_ip', '$payment_action', '$item_id' , '$item_name' , '$user', '$payment_year', '$payment_month', '$payment_day', '$payment_time'"
        ];

        $SQL->build($insert_query);
        $_SESSION['kj_payment']['db_id']         = $SQL->insert_id();
        $_SESSION['kj_payment']['payment_token'] = $payment_token;
        $foundedAction                           = false;

        // if the payment is for joining a group and the payer is in login and member in kleeja
        if ($_SESSION['kj_payment']['payment_action'] == 'join_group' && $usrcp->name())
        {
            $foundedAction               = true;
            $this->toGlobal['groupName'] = $_SESSION['kj_payment']['item_name'];
            $update_user                 = [
                'UPDATE'       => "{$dbprefix}users",
                'SET'          => "group_id = '" . $_SESSION['kj_payment']['item_id'] . "'" ,
                'WHERE'        => "id = '" . $usrcp->id() . "'"  ,
            ];

            $SQL->build($update_user);
        }
        elseif ($_SESSION['kj_payment']['payment_action'] == 'buy_file')
        {
            $foundedAction               = true;
            $this->downloadLinkMailer    = $usrcp->mail();
            $this->toGlobal['down_link'] = $config['siteurl'] . 'do.php?downPaidFile=' . $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $_SESSION['kj_payment']['payment_token'];
            $this->toGlobal['file_name'] = $_SESSION['kj_payment']['item_name'];
            $user_id                     = getFileInfo($_SESSION['kj_payment']['item_id'], 'user')['user']; // File Owner ID
            $user_group                  = $usrcp->get_data('group_id', $user_id)['group_id']; // get the group id
            if (user_can('recaive_profits', $user_group))
            {
                // becuse the payment is successfuly , let's give some profits to the file owner
                $user_profits = $payment_amount * $config['file_owner_profits'] / 100;
                $SQL->query("UPDATE {$dbprefix}users SET `balance` = balance+{$user_profits} WHERE id = {$user_id}");
            }
        }

        if (! $foundedAction)
        {
            $toGlobal = [];
            //export here $toGlobal and do what u want
            is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:notFoundedAction_' . $_SESSION['kj_payment']['payment_action'], get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
            if (count($toGlobal) !== 0)
            {
                foreach ($toGlobal as $key => $value)
                {
                    $this->toGlobal[$key] = $value;
                }
            }
        }
        // now we can say that the payment made successfuly
        $this->successPayment = true;
    }

    public function isSuccess()
    {
        return $this->successPayment;
    }


    public function getGlobalVars()
    {
        return $this->toGlobal;
    }

    public function linkMailer()
    {
        return $this->downloadLinkMailer;
    }


    public function createPayout($itemInfo)
    {
        return false;
    }

    public function checkPayout($payoutInfo)
    {
        return false;
    }

    public static function permission($permission)
    {
        switch ($permission) 
        {
            case 'createPayment':
                return true;

                break;

          case 'createPayout': // sending money to users
              return false;

              break;

          case 'checkPayouts':
              return false;

              break;

            default:
                return false;

                break;
        }
    }
}
