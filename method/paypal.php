<?php

/*
 * Th Default Method of Kleeja Payment
 * PayPal Method
 */

 // PayPal PHP SDK



class kjPayMethod_paypal implements KJPaymentMethod
{
    
    private $apiContext; // client id and cliend secret , we need it to create payment and checking it
    private $currency;
    private $successPayment = false; // its return the payment state after checking it
    private $varsForCreate  = array(); // some methods will work in kleeja without leaving the website
    private $toGlobal = array(); // the list of vars that we want to export it to kleeja
    private $downloadLinkMailer = false; // the mail that we want to send download link to it 
    private $checkCookie = false;


    public function paymentStart()
    {
        global $config;
        require_once dirname(__FILE__) . '/../vendor/autoload.php';

        $apiContext  = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                trim($config['pp_client_id']) ,
                trim($config['paypal_client_secret'])
                )
        );

        $this->apiContext = $apiContext ;


    }

    public function setCurrency($currency)
    {
        $this->currency = strtoupper($currency);
    }



    // do = buy_file OR join_group
    // info = is an array about File infrmations or group informations
    // check getFileInfo function and getGroupInfo function


    public function CreatePayment($do , $info = array() )
    {
        global $config , $usrcp , $SQL , $dbprefix;
        // start ..

        $payer = new \PayPal\Api\Payer();
            $payer->setPaymentMethod('paypal');

            $item = new PayPal\Api\Item();
            $item->setName( ( $do == 'buy_file' ? 'File : ' . $info['real_filename'] : 'Join Group : ' . $info['name'] ) )
            ->setCurrency( $this->currency )
            ->setQuantity( 1 )
            ->setSku(  $info['id']  ) // its like ItemNumber .
            ->setPrice( $info['price'] );


            $itemList = new PayPal\Api\ItemList();
            $itemList->setItems(array($item));

            $amount = new \PayPal\Api\Amount();
            $amount->setTotal( $info['price'] );
            $amount->setCurrency( $this->currency );


            $transaction = new \PayPal\Api\Transaction();
            $transaction->setAmount($amount)
            ->setDescription('Payment made by Kleeja ( kj_paypal ) plugin')
            ->setItemList($itemList);

            $redirectUrls = new \PayPal\Api\RedirectUrls();
            $redirectUrls->setReturnUrl( $config['siteurl'] . 'go.php?go=kj_payment&method=paypal&action=check&state=success' )
                         ->setCancelUrl( $config['siteurl'] . 'go.php?go=kj_payment&method=paypal&action=check&state=cancel' );

            $payment = new \PayPal\Api\Payment();
            $payment->setIntent('sale')
               ->setPayer($payer)
               ->setTransactions(array($transaction))
               ->setRedirectUrls($redirectUrls);



    try {
        $payment->create($this->apiContext);
        
        $payment_info = json_decode($payment , true);

        $payment_method    = 'paypal';
        $payment_state     = 'created';
        $payment_currency  = $this->currency;
        $payment_action    = $do ;
        $payment_token     = createToken() ;
        $payment_amount    = $info['price'];
        $payment_payer_ip  = get_ip();
        $item_id           = $info['id'];
        $item_name         = $do === 'buy_file' ? $info['real_filename'] : $info['name'];
        $user              = $usrcp->name() ? $usrcp->id() : 0;
        $payment_year      = date("Y");
        $payment_month     = date("m");
        $payment_day       = date("d");
        $payment_time      = date("H:i:s");

        $payment_more_info = payment_more_info('to_db' , array(
            'paypal_payment_id'    => $payment_info['id'] ,
            'paypal_payment_token' => explode('token=' , $payment->getApprovalLink())[1] ,
        )); 
        
        $insert_query	= array(
            'INSERT'	=> 'payment_state , payment_method , payment_more_info , payment_amount , payment_currency , payment_token , payment_payer_ip , payment_action , item_id , item_name , user , payment_year , payment_month , payment_day , payment_time',
            'INTO'		=> "{$dbprefix}payments",
            'VALUES'	=> "'$payment_state', '$payment_method' ,'$payment_more_info', '$payment_amount', '$payment_currency','$payment_token', '$payment_payer_ip', '$payment_action', '$item_id' , '$item_name' , '$user', '$payment_year', '$payment_month', '$payment_day', '$payment_time'"
        );

        $SQL->build($insert_query);

        if ($SQL->affected()) 
        {
    
            $_SESSION['kj_payment'] = array
            (
                'db_id'                 => $SQL->insert_id() ,
                'paypal_payment_id'     => $payment_info['id'] ,
                'paypal_payment_token'  => explode('token=' , $payment->getApprovalLink())[1] ,
                'payment_token'         => $payment_token,
                'payment_action'        => $do ,
                'item_id'               => $item_id ,
            );
    
            redirect( $payment->getApprovalLink() );
            $this->varsForCreate['no_request'] = true;
    
        }
        
    
      

    }
    catch (\PayPal\Exception\PayPalConnectionException $ex) {
        // This will print the detailed information on the exception.
        //REALLY HELPFUL FOR DEBUGGING
        $this->err =  $ex->getData();
    }

        // End
    }


    public function varsForCreatePayment()
    {
        return $this->varsForCreate;
    }


    public function isSuccess()
    {
        return $this->successPayment;
    }


    public function getGlobalVars()
    {
        return $this->toGlobal;
    }


    public function checkPayment()
    {
        global $SQL , $dbprefix , $THIS_STYLE_PATH_ABS , $config , $usrcp;

        $success_payment = ig('state') ? ( g('state') === 'success' ? true : false ) : false;

        if ($success_payment) 
        {

            if ( ig('paymentId') && ig('token') && ig('PayerID') ) // paypal will send to us this varibels
            {

                $PaymentInfo = $_SESSION['kj_payment'];

                if ( g('paymentId') == $PaymentInfo['paypal_payment_id'] && g('token') == $PaymentInfo['paypal_payment_token'] ) 
                {

                    $db_Payment_Info = getPaymentInfo( $PaymentInfo['db_id'] );

                    if ($db_Payment_Info) 
                    {
                        $paymentId = g('paymentId');
                        $payment   = PayPal\Api\Payment::get($paymentId, $this->apiContext);
                        $payerId   = g('PayerID');
                
                        // Execute payment with payer ID
                        $execution = new PayPal\Api\PaymentExecution();
                        $execution->setPayerId($payerId);
                
                        try {
                          // Execute payment
                          $result = $payment->execute($execution, $this->apiContext);
                          $PayPal_Payment_info = json_decode($result , true);
                          
                          // To facilitate the work
                          $PPI = $PayPal_Payment_info ;

                          $payment_more_info = payment_more_info('to_db' , array(
                              'paypal_payment_fees' => $PPI['transactions'][0]['related_resources'][0]['sale']['transaction_fee']['value'] ,
                              'paypal_payer_name' => $PPI['payer']['payer_info']['first_name'] . ' ' . $PPI['payer']['payer_info']['last_name'] ,
                              'paypal_payer_mail' => $PPI['payer']['payer_info']['email'] ,
                              'paypal_payer_id'   => $PPI['payer']['payer_info']['payer_id'] ,
                              'paypal_payment_id'  => $PPI['id']
                          ));


                          $update_query	= array(
                            'UPDATE'	=> "{$dbprefix}payments",
                            'SET'		=> "payment_state = 'approved' , payment_more_info = '$payment_more_info'",
                            'WHERE'		=> 'id = ' . $PaymentInfo['db_id'] . " AND payment_state = 'created'" ,
                        );

                        $SQL->build($update_query);

                        // if the payment is for joining a group and the payer is in login and member in kleeja
                        if ($PaymentInfo['payment_action'] == 'join_group' && $usrcp->name() )
                        {
                            $update_user	= array(
                                'UPDATE'	=> "{$dbprefix}users",
                                'SET'		=> "group_id = '" . $PaymentInfo['item_id'] . "'" ,
                                'WHERE'		=> "id = '" . $usrcp->id() . "'"  ,
                            );

                            $SQL->build($update_user);
                        }
                        


                        // now we can say that the payment made successfuly
                        $this->successPayment = true;

                        // send download link to the buyer
                        // send varible to global -> go.php

                        if ( $PaymentInfo['payment_action'] == 'buy_file' ) 
                        {
                            $this->downloadLinkMailer = $PPI['payer']['payer_info']['email'];
                            $this->toGlobal['down_link'] = $config['siteurl'] . 'do.php?downPaidFile=' . $_SESSION['kj_payment']['item_id'] . '_'.$db_Payment_Info['id'] . '_' . $db_Payment_Info['payment_token'];
                            $this->toGlobal['file_name'] = $db_Payment_Info['item_name'];

                        }else // payment_action = join_group
                        {
                            $this->toGlobal['groupName'] = $db_Payment_Info['item_name'];
                            unset($_SESSION['kj_payment']);
                        }
                          
                
                          
                
                        } catch (PayPal\Exception\PayPalConnectionException $ex) {
                          echo $ex->getCode();
                          echo $ex->getData();
                
                          die($ex);
                         } catch (Exception $ex) {
                          die($ex);
                        }
                    }
                }

            }

        }else
        {
            $payment_info = getPaymentInfo( $_SESSION['kj_payment']['db_id'] , '' , true );
                
            if ( $payment_info && g('token') == $payment_info['paypal_payment_token']) 
            {
                
                $delete_query	= array(
                    'DELETE' => "{$dbprefix}payments",
                    'WHERE'	=> 'id = ' . $_SESSION['kj_payment']['db_id']
                );

                $SQL->build( $delete_query );

                unset( $_SESSION['kj_payment'] );

                if ($payment_info['payment_action'] == 'buy_file') 
                {
                    redirect( $config['siteurl'] . 'do.php?file=' . $payment_info['item_id'] );

                }elseif ( $payment_info['payment_action'] == 'join_group' ) 
                {
                    redirect( $config['siteurl'] . 'go.php?go=paid_group' );
                }

                exit;
            }
        }


    }


    // return the e-mail adress that kleeja have to send download link to it
    // if you are working with a method that dont have an e-mail adress , return 'false';
    // then kleeja will display a form for user to enter the e-mail adress to recive the download link
    // called if checking payment is successful only
    public function linkMailer()
    {
        return false;
        //return 'mitanomar@gmail.com';
        //return $this->downloadLinkMailer;
    }
    


    /**
     * Mayyyyyyyyyybe Later
     *
    public static function permission($permission)
    {
        switch ($permission) 
        {
            case 'createPayment':
                return true;
                break;
            
          case 'createPayout': // sending money to users
              return true;
              break;

          case 'sendingInvoice': // sending invoice to users
              return true;
              break;
            
            default:
                return false;
                break;
        }
    }

    */


}