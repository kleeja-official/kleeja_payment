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

        $result = $SQL->query("SELECT u.id , u.name , u.package , u.package_expire , u.group_id FROM {$dbprefix}users u");
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

        return false;
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

        // first , let's check if the file owner have receive profits permissions
        $file_owner = getFileInfo($file_id)['user'];

        $file_owner_group = $this->users[$file_owner]['group_id'];

        if (! user_can('recaive_profits', $file_owner_group))
        {
            return;
        }

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
            $SQL->query("UPDATE `{$dbprefix}users` SET `subs_point` = subs_point+1 WHERE `id` = {$file_owner}");
        }
    }

    /**
     * to convert the subscription points to amount
     * we need to know which user have not valid subscription and the subscriptiion id id not zero in the db
     * then we need to get the subscription information & and the file owner profits persentage
     * point price = ($subscription_info['price'] * $config['kjp_file_owner_profits'] / 100) / $pointsCount;
     * @return void
     */
    public function convertPoints()
    {
        global $SQL , $dbprefix , $config;

        $paidFiles = [];

        // get all paid files one time by one call
        $files = $SQL->query("SELECT id , user FROM {$dbprefix}files WHERE price > 0");

        while ($file = $SQL->fetch($files))
        {
            $paidFiles[$file['id']]            = $file;
            $paidFiles[$file['id']]['points']  = 0;
            $paidFiles[$file['id']]['profits'] = 0;
        }

        // first match
        foreach ($this->users as $user)
        {
            if ($user['package'] && ! $this->is_valid($user['id']))
            {
                $subscription_info = $this->subscriptions[$user['package']];

                if (! $subscription_info)
                {
                    continue;
                }
                $pointsQuery       = $SQL->query("SELECT * FROM {$dbprefix}subscription_point WHERE user = {$user['id']}");

                $pointsCount = $SQL->num_rows($pointsQuery);

                $pointPrice = ($subscription_info['price'] * $config['kjp_file_owner_profits'] / 100) / $pointsCount;

                while ($points = $SQL->fetch($pointsQuery))
                {
                    if ($paidFiles[$points['file_id']]['user'])
                    { // the file owner is not guest
                        $paidFiles[$points['file_id']]['points']++;

                        if (! isset($this->users[$paidFiles[$points['file_id']]['user']]['profit']))
                        { // please focuse
                            $this->users[$paidFiles[$points['file_id']]['user']]['profit'] = 0;
                        }
                        $this->users[$paidFiles[$points['file_id']]['user']]['profit'] += $pointPrice;

                        if (! isset($this->users[$paidFiles[$points['file_id']]['user']]['taked_points']))
                        { // please focuse
                            $this->users[$paidFiles[$points['file_id']]['user']]['taked_points'] = 0;
                        }
                        $this->users[$paidFiles[$points['file_id']]['user']]['taked_points']++;
                    }
                }
                $SQL->query("DELETE FROM {$dbprefix}subscription_point WHERE user = {$user['id']}");

                $SQL->query("UPDATE {$dbprefix}users SET `package` = 0 , `package_expire` = 0 WHERE id = {$user['id']}");
            }
        }

        // second match
        foreach ($this->users as $u)
        {
            if (isset($u['profit']) && $u['profit'] > 0)
            {
                $SQL->query("UPDATE {$dbprefix}users SET `balance` = balance+{$u['profit']} , `subs_point` = subs_point-{$u['taked_points']} WHERE `id` = '{$u['id']}'");
            }
        }
    }
}
