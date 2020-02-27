<?php

/**
 * @package kleeja_payment
 * the class interface of all payment method that's included in kleeja payment plugin,
 * #made_with_love_for_kleeja
 */

interface KJPaymentMethod
{
    /**
     * include your files and connect with your api server
     * and do any steps becuse we have to be active next min
     */
    public function paymentStart(): void;


    /**
     * i will give to you the currency that we work with it ,
     * @param string $currency
     * @return void
     */
    public function setCurrency(string $currency): void;


    /**
     * Creating a Payments for any action you want
     * @param string $do -> the action name (in the default case, there is three action , /buy_file/join_group/subscripe)
     * @param array $info -> it's an array about item informations: (id & name & price),
     * @example: there is three function that return the content of $info variable, getFileInfo(), getGroupinfo(), subscription::get()
     * @return void
     */
    public function CreatePayment(string $do, array $info): void;


    /**
     * this function is called after calling create payment function
     * it ruturn all variable that kleeja need to know about it,
     * like some information that you want to give it to the template, because some method are connecting to its payment server via JS, like Stripe
     * @return array
     */
    public function varsForCreatePayment(): ? array;


    /**
     * the payment is made, we need to check the status of payment, is it made successfuly or not
     * and including the action of successful payment and failed payment
     * @example: change the user group after making a payment for join a group
     * @example: make a subscription for the user after subscriping to in a package
     * @return void
     */
    public function checkPayment(): void;


    /**
     * this function is called after checkPayment() & checkPayout() function 
     * @example: after calling checkPayment function, we need to know the payment status, is it made successfuly or not
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * this function will be called after checkPayment() function && the return value of isSuccess() function is (boolean TRUE)
     * @return array [key => value], the key have to be the variable name, and the value will be the variable value,
     * it's another method to compacting ans extracting array,
     * because the variable that the payment method have to give it to kleeja is unknow
     */
    public function getGlobalVars(): ? array;


    /**
     * this function after successful payment, it's the email address that we need to send the payment invoices to it
     * email is not included in all payment method, and it's not required in all payments action, NOW we need it only for buy_file action
     * @return string
     */
    public function linkMailer(): ? string;


    /**
     * a function to seding money to our members
     * after sending a request from user to withdraw the money to his account & and the admin accept the request
     * & the pay method have payout permission, then this function will be called
     * @param array $itemInfo -> it's information about money receiver [email address, money amount]
     * @return void
     * note: some payment method need time for to make a payout, if your method need a time, then update the payout status to sent,
     * and include checking steps in checkPayout() function, and if you are sure, then update the payout status to 'recived'
     */
    public function createPayout(array $itemInfo): void;


    /**
     * in some cases, the payout need a time to be finished, then after creating a payout, and the payout status is sent,
     * & the admin request to check the payout status, then this function will be called.
     * @param array $payoutInfo
     * @return void
     */
    public function checkPayout(array $payoutInfo): void;


    /**
     * for each payment method, there is some cases that's not included all feature,
     * that's why for each feature that kleeja is using it, there is a name for it, like createPayment or createPayout.
     * that's why before calling the functio that's using a feater, we will call perrmission() function,
     * to check if this pay method is supporting this feature or not
     * @param string $permission, 'permission name'
     * @return bool (is this feature is included or not: ^_^ i know you are smart)
     */
    public static function permission(string $permission): bool;
}


// Made with Love For Kleeja
// Mitan :)
