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

    public function expire_at($subscripe_id)
    {
        $subscripe = $this->subscriptions[$subscripe_id];

        if (! $subscripe)
        {
            return false;
        }

        return time() + ($subscripe['days'] * 86400);
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
}
