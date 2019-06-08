<?php


interface KJPaymentMethod
{
    // include your files and connect with your api server
    // and do any steps becuse we have to be active next min
    public function paymentStart();


    // i will give to you the currency that we work with it ,
    // und du kannst Tomaten verkaufen in der Strasse
    public function setCurrency($currency);


    // Creating Payments for selling files or joining the groups
    // do = buy_file OR join_group
    // info = is an array about File infrmations or group informations
    // check getFileInfo() function and getGroupInfo() function;
    public function CreatePayment($do, $info);


    // we call this function after create Payment
    // it return an array about all varibles that the method need to give it to kleeja
    // becuse maybe the method will work here
    public function varsForCreatePayment();


    // after we made the payment and the user paid the money
    // we have to check if the payment was success or not
    // and also updating the user group if the payment was for oining groups
    public function checkPayment();



    // this function will be called after checkPayment() function and CreatePayment() Function
    // it shoud to return 'true' if Check payment was success or Payment Created successfuly , and 'false' if it's not
    public function isSuccess();


    // this function will be called isSuccess() function , if isSuccess() function return true
    // this function have to return an array , the key of this array will become varibels
    // we don't know what we have to compact ;
    public function getGlobalVars();


    // return the e-mail adress that kleeja have to send download link to it
    // if you are working with a method that dont have an e-mail adress , return 'false';
    // then kleeja will display a form to enter the e-mail adress to recive the download link
    // called after successful payments
    public function linkMailer();


    // the plugin will fetch the data from db and insert the content to this function as array
    // it will do the the steps of creating payments , like running paymentStart function and set the currency
    //NOTE: update the state of payout to "sent" , if you need to check is the payout recaived or not
    // but if you are sure that it will be recaived , update it to recived
    public function createPayout($itemInfo);


    // after view the payout , if the state is "sent" , it will call this function
    // and the data will get from db table
    public function checkPayout($payoutInfo);


    // befor calling any of function (CreatePayment , createPayout , checkPayout)
    // we will need to check if the method support calling this function or not
    //an example is included in the default methods
    public static function permission($permission);
}


// Made with Love For Kleeja
// Mitan :)
