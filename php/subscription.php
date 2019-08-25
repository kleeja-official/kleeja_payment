<?php

// prevent illegal run
if (! defined('IN_PLUGINS_SYSTEM'))
{
    exit;
}

class Subscription
{
    private $users         = [];
    private $subscriptions = [];

    public function __construct()
    {
        global $SQL , $dbprefix , $config;

        if (! $config['kjp_active_subscriptions'])
        {
            return;
        }

        $result = $SQL->query("SELECT u.id , u.name , u.package , u.package_expire FROM {$dbprefix}users u");
        while ($user = $SQL->fetch($result))
        {
            $this->users[$user['id']] = $user;
        }

        $subs = $SQL->query("SELECT * FROM {$dbprefix}subscriptions");
        while ($sub = $SQL->fetch($subs))
        {
            $this->subscriptions[$sub['id']] = $sub;
        }
    }

    public function expire_at($subscripe_id, $time = false)
    {
        $subscripe = $this->subscriptions[$subscripe_id];

        if (! $subscripe)
        {
            return false;
        }

        return ($time ? $time : time()) + ($subscripe['days'] * 86400);
    }

    public function get($subscripe_id = 0)
    {
        if ($subscripe_id)
        {
            return $this->subscriptions[$subscripe_id] ?? false;
        }
        else
        {
            return $this->subscriptions;
        }
    }

    public function user_subscripe($user_id)
    {
        return $this->subscriptions[$this->users[$user_id]['package']] ?? '';
    }

    /**
     * check if a user have subscription or not
     *
     * @param mixed $user_id
     */
    public function is_valid($user_id = 0)
    {
        $user = $this->users[$user_id];

        if (! $user || ! $user['package'])
        {
            return false;
        }

        $time_now  = time();
        $expire_at = $user['package_expire'];

        if ($time_now < $expire_at)
        {
            return true;
        }
        else
        {
            $this->clear($user_id);
            return false;
        }
    }


    public function getMembersCount($subscripe_id)
    {
        $count = 0;

        foreach ($this->users as $user)
        {
            if ($user['package'] == $subscripe_id && $this->is_valid($user['id']))
            {
                $count++;
            }
        }
        return $count;
    }

    public function addPoint($file_id)
    {
        /**
         * before you checking this functiion , remember -> GUEST DONT HAVE SUBSCRIPTION
         * done -> now check the function
         */
        global $SQL , $dbprefix , $usrcp;

        $user             = $usrcp->id();
        $time             = time();
        $subscription_id  = $this->user_subscripe($user)['id'];
        // ssubscription_hash -> maybe this user renew the subscription and download this file again , so we need to add a point also again
        $subscripe_hash   = sha1($user . $subscription_id . $this->users[$user]['package_expire']);

        $check_point = $SQL->query("SELECT * FROM `{$dbprefix}subscription_point` WHERE `user` = {$user} AND `file_id` = {$file_id} AND `subscripe_hash` = '{$subscripe_hash}'");

        if (! $SQL->num_rows($check_point))
        { // this is first time !!
            $query       = [
                'INSERT' => 'user , file_id , subscription_id  , subscripe_hash , time',
                'INTO'   => "{$dbprefix}subscription_point",
                'VALUES' => "$user , $file_id , $subscription_id  , '{$subscripe_hash}' , $time",
            ];
            $SQL->build($query);
            $file_owner = getFileInfo($file_id)['user'];
            $SQL->query("UPDATE `{$dbprefix}users` SET `subs_point` = subs_point+1 WHERE `id` = {$file_owner}");
        }
    }

    private function clear($user_id)
    {
        global $SQL , $dbprefix;
        $SQL->query("UPDATE `{$dbprefix}users` SET `package` = 0 , `package_expire` = 0 WHERE `id` = {$user_id}");
    }
}
