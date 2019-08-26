<?php

class kjPayMethod_cards implements KJPaymentMethod
{
    private $currency;
    private $successPayment     = false; // its return the payment state after checking it
    private $varsForCreate      = []; // some methods will work in kleeja without leaving the website
    private $toGlobal           = []; // the list of vars that we want to export it to kleeja
    private $downloadLinkMailer = false; // the mail that we want to send download link to it


    public function paymentStart()
    {
        global $config;
        require_once dirname(__FILE__) . '/../stripe-sdk/vendor/autoload.php';

        $stripe = [
            'secret_key'      => trim($config['kjp_stripe_secret_key']),
            'publishable_key' => trim($config['kjp_stripe_publishable_key']),
        ];

        \Stripe\Stripe::setApiKey($stripe['secret_key']);
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * the
     * @param mixed $do
     * @param mixed $info
     */
    public function CreatePayment($do, $info)
    {
        global $config , $olang ,$THIS_STYLE_PATH_ABS;
        // we will only say to kleeja where is the template file , and send some text to the user interface ,
        // we don't need to do anythink now , when the user pay the amount to stripe
        // then we will insert the data to the database
        // if the payment wasn't succes , nothing will hapend

        $_SESSION['kj_payment'] =
        [
            'payment_action'    => $do ,
            'item_id'           => $info['id'] ,
            'item_name'         => $info['name'] ,
        ];

        $this->varsForCreate['no_request']             = false;
        $this->varsForCreate['titlee']                 = 'Pay By Card';
        $this->varsForCreate['stylee']                 = 'pay_card';
        $this->varsForCreate['styleePath']             = file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/pay_card.html') ? $THIS_STYLE_PATH_ABS : dirname(__FILE__) . '/../html/';
        $this->varsForCreate['FormAction']             = $config['siteurl'] . 'go.php?go=kj_payment&method=cards&action=check';
        $this->varsForCreate['payAction']              = sprintf($olang['KJP_ACT_' . strtoupper($do)], $info['name']);
        $this->varsForCreate['storeName']              = strtoupper($config['sitename']);
        $this->varsForCreate['storeIcon']              = $config['siteurl'] . 'images/apple-touch-icon.png';
        $this->varsForCreate['paymentCurrency']        = $this->currency;
        $this->varsForCreate['paymentAmount']          = $this->convertPrice($info['price']);
        $this->varsForCreate['stripe_publishable_key'] = $config['kjp_stripe_publishable_key'];
    }


    public function varsForCreatePayment()
    {
        return $this->varsForCreate;
    }


    public function checkPayment()
    {
        global $config , $usrcp , $SQL , $dbprefix , $d_groups ,$olang, $subscription;

        if (! isset($_SESSION['kj_payment']) || empty($_SESSION['kj_payment']))
        {
            kleeja_err('What Are U Doing Here ??', '', true, $config['siteurl']);

            exit;
        }
        elseif (($_SESSION['kj_payment']['payment_action'] == 'buy_file') && ! $itemInfo = getFileInfo($_SESSION['kj_payment']['item_id']))
        {
            kleeja_err('ERROR REQUEST', '', true, $config['siteurl']);

            exit;
        }
        elseif (($_SESSION['kj_payment']['payment_action'] == 'join_group') && ! $itemInfo = getGroupInfo($d_groups, $_SESSION['kj_payment']['item_id']))
        {
            kleeja_err('ERROR REQUEST', '', true, $config['siteurl'] . 'go.php?go=paid_group');

            exit;
        }
        elseif (($_SESSION['kj_payment']['payment_action'] == 'subscripe') && ! $itemInfo = $subscription->get($_SESSION['kj_payment']['item_id']))
        {
            kleeja_err('ERROR REQUEST', '', true, $config['siteurl'] . 'go.php?go=subscription');

            exit;
        }

        //export here $itemInfo
        is_array($plugin_run_result = Plugins::getInstance()->run('KjPay:itemInfoExport_' . $_SESSION['kj_payment']['payment_action'], get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


        try
        {
            $token  = p('stripeToken');
            $email  = p('stripeEmail');

            $customer = \Stripe\Customer::create([
                'email'   => $email,
                'source'  => $token,
            ]);

            $charge = \Stripe\Charge::create([
                'customer' => $customer->id,
                'amount'   => $this->convertPrice($itemInfo['price']),
                'currency' => $this->currency,
            ]);

            if ($charge->paid  && $charge->amount == $this->convertPrice($itemInfo['price']))
            {
                // insert to the DataBase
                $payment_method    = 'cards';
                $payment_state     = 'approved';
                $payment_currency  = $this->currency;
                $payment_action    = $_SESSION['kj_payment']['payment_action'];
                $payment_token     = createToken();
                $payment_amount    = $itemInfo['price'];
                $payment_payer_ip  = get_ip();
                $item_id           = $_SESSION['kj_payment']['item_id'];
                $item_name         = $itemInfo['name'];
                $user              = $usrcp->name() ? $usrcp->id() : 0;
                $payment_year      = date('Y');
                $payment_month     = date('m');
                $payment_day       = date('d');
                $payment_time      = date('H:i:s');

                // information from Stripe

                $stripe_buyer_mail       = $charge->billing_details->name; // yes , the name return the mail
                $stripe_transaction_id   = $charge->id; // the transaction id
                $card                    = $charge->payment_method_details->card; // the list of all ditails of the card
                $stripe_card_type        = $card->brand; // visa or master or ...
                $stripe_card_funding     = $card->funding; // credit card or prepaid card
                $stripe_card_country     = $card->country; // the card country
                $stripe_card_expire_date = $card->exp_month . ' / ' . $card->exp_year;
                $stripe_card_fingerprint = $card->fingerprint; // its like uniq id of the card
                $stripe_card_last_4nums  = $card->last4;




                $payment_more_info = payment_more_info('to_db', [
                    'stripe_transaction_id'   => $stripe_transaction_id ,
                    'stripe_buyer_mail'       => $stripe_buyer_mail ,
                    'stripe_card_type'        => $stripe_card_type ,
                    'stripe_card_funding'     => $stripe_card_funding ,
                    'stripe_card_country'     => $stripe_card_country ,
                    'stripe_card_expire_date' => $stripe_card_expire_date ,
                    'stripe_card_last_4nums'  => $stripe_card_last_4nums ,
                    'stripe_card_fingerprint' => $stripe_card_fingerprint ,
                ]);

                $insert_query    = [
                    'INSERT'      => 'payment_state , payment_method , payment_more_info , payment_amount , payment_currency , payment_token , payment_payer_ip , payment_action , item_id , item_name , user , payment_year , payment_month , payment_day , payment_time',
                    'INTO'        => "{$dbprefix}payments",
                    'VALUES'      => "'$payment_state', '$payment_method' ,'$payment_more_info', '$payment_amount', '$payment_currency','$payment_token', '$payment_payer_ip', '$payment_action', '$item_id' , '$item_name' , '$user', '$payment_year', '$payment_month', '$payment_day', '$payment_time'"
                ];

                $SQL->build($insert_query);
                $_SESSION['kj_payment']['db_id']         = $SQL->insert_id();
                $_SESSION['kj_payment']['payment_token'] = $payment_token;


                $foundedAction = false;
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
                elseif ($_SESSION['kj_payment']['payment_action'] == 'subscripe' && $usrcp->name())
                {
                    $foundedAction               = true;
                    $package_expire              = $subscription->expire_at($_SESSION['kj_payment']['item_id']);
                    $olang['KJP_JUIN_SUCCESS']   = sprintf($olang['KJP_SUCCESS_SUBSCRIPE'], $_SESSION['kj_payment']['item_name'], date('Y/m/d', $package_expire));
                    $this->toGlobal['olang']     = $olang;
                    $update_user                 = [
                        'UPDATE'       => "{$dbprefix}users",
                        'SET'          => 'package = ' . $_SESSION['kj_payment']['item_id'] . " , package_expire = {$package_expire}",
                        'WHERE'        => "id = '" . $usrcp->id() . "'"  ,
                    ];

                    $SQL->build($update_user);
                }
                elseif ($_SESSION['kj_payment']['payment_action'] == 'buy_file')
                {
                    $foundedAction               = true;
                    $this->downloadLinkMailer    = $stripe_buyer_mail;
                    $this->toGlobal['down_link'] = $config['siteurl'] . 'do.php?downPaidFile=' . $_SESSION['kj_payment']['item_id'] . '_' . $_SESSION['kj_payment']['db_id'] . '_' . $payment_token;
                    $this->toGlobal['file_name'] = $_SESSION['kj_payment']['item_name'];
                    $user_id                     = getFileInfo($_SESSION['kj_payment']['item_id'], 'user')['user']; // File Owner ID
                    $user_group                  = $usrcp->get_data('group_id', $user_id)['group_id']; // get the group id
                    if (user_can('recaive_profits', $user_group))
                    {
                        // becuse the payment is successfuly , let's give some profits to the file owner
                        $user_profits = $payment_amount * $config['kjp_file_owner_profits'] / 100;
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
        }
        catch (\Throwable $th)
        {
            //throw $th;
        }
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



    private function convertPrice($price)
    {
        $p = explode('.', $price);

        // EX: 12 USD
        if (count($p) == 1)
        {
            $price .= '00'; // leave it as string -> int 00 = int 0
        }
        // EX: 12.95
        elseif (count($p) == 2)
        {
            $price = str_replace('.', '', $price);
            // maybe 12.5
            if (strlen($p[1] == 1))
            {
                $price .= '0';
            }
        }

        return $price;
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
