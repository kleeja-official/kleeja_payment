<?php

/*
 *
 * Download page -> User interface
 * it's a copy from 'do.php' file ( line 31 to line 143 ) with some change
 * i changed all hook names and other changes that i made , i added "edited" like a comment -> search about 'edited' word
 *
 */
define('IN_PAID_DOWNLOAD' , true);

is_array($plugin_run_result = Plugins::getInstance()->run('KJP:begin_download_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

$query = [
    'SELECT'       => 'f.id, f.real_filename, f.name, f.folder, f.size, f.time, f.uploads, f.type , f.price', // edited -> f.price
    'FROM'         => "{$dbprefix}files f",
    'LIMIT'        => '1',
];

//if user system is default, we use users table
if ((int) $config['user_system'] == 1)
{
    $query['SELECT'] .= ', u.name AS fusername, u.id AS fuserid';
    $query['JOINS']    =    [
        [
            'LEFT JOIN'    => "{$dbprefix}users u",
            'ON'           => 'u.id=f.user'
        ]
    ];
}

    $query['WHERE']    = 'f.id=' . g('file', 'int'); // edited


is_array($plugin_run_result = Plugins::getInstance()->run('KJP:qr_download_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
$result    = $SQL->build($query);

if ($SQL->num_rows($result) !== 0)
{
    $file_info = $SQL->fetch_array($result);

    $SQL->freeresult($result);

    // user dont have to be here if the file is for free

    if ($file_info['price'] == 0)
    {
        redirect($config['siteurl'] . 'do.php?id=' . g('file')); // edited
        exit;
    }


    // some vars
    $id            = $file_info['id'];
    $name          = $fname         = $file_info['name'];
    $real_filename = $file_info['real_filename'];
    $type          = $file_info['type'];
    $size          = $file_info['size'];
    $time          = $file_info['time'];
    $uploads       = $file_info['uploads'];
    $price         = $file_info['price']; // edited


    $fname2        = str_replace('.', '-', htmlspecialchars($name));
    $name          = $real_filename != '' ? str_replace('.' . $type, '', htmlspecialchars($real_filename)) : $name;
    $name          = strlen($name)                                        > 70 ? substr($name, 0, 70) . '...' : $name;
    $fusername     = $config['user_system'] == 1 && $file_info['fuserid'] > -1 ? $file_info['fusername'] : false;
    $userfolder    = $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $file_info['fuserid'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $file_info['fuserid']);
    // edited -> g('filename') does not work here
    $url_file    = $config['mod_writer'] ? $config['siteurl'] . 'down-' . $file_info['id'] . '.html' : $config['siteurl'] . 'do.php?down=' . $file_info['id'];

    if (! empty($config['livexts']))
    {
        $livexts = explode(',', $config['livexts']);

        if (in_array($type, $livexts))
        {
            // edited -> g('filename') does not work here
            $url_filex    = $config['mod_writer'] ? $config['siteurl'] . 'downex-' . $file_info['id'] . '.html' : $config['siteurl'] . 'do.php?downex=' . $file_info['id'];

            redirect($url_filex, false);
        }
    }

    $REPORT        = ($config['mod_writer']) ?  $config['siteurl'] . 'report-' . $file_info['id'] . '.html' :  $config['siteurl'] . 'go.php?go=report&amp;id=' . $file_info['id'];
    $seconds_w     = user_can('enter_acp') ? 0 : $config['sec_down'];
    $time          = kleeja_date($time);
    $size          = readable_size($size);

    $file_ext_icon = file_exists('images/filetypes/' . $type . '.png') ? 'images/filetypes/' . $type . '.png' : 'images/filetypes/file.png';

    $is_style_supported = is_style_supported(); // edited

    $sty        = 'pay_download'; // edited

    // to allow the developer to make 'pay_download.html' with their styles .
    $styPath       =  file_exists($THIS_STYLE_PATH_ABS . 'kj_payment/pay_download.html') ? $THIS_STYLE_PATH_ABS . 'kj_payment' :  dirname(__FILE__) . '/../html/'; // edited
    $title         =  $name . ' - ' . 'شراء'; // edited
}
else
{
    //file not exists
    is_array($plugin_run_result = Plugins::getInstance()->run('KJP:not_exists_qr_downlaod_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook
    kleeja_err($lang['FILE_NO_FOUNDED']);
}

// $show_style = true;   edited

is_array($plugin_run_result = Plugins::getInstance()->run('KJP:b4_showsty_downlaod_file', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

//add http reffer to session to prevent errors with some browsers !
$_SESSION['HTTP_REFERER'] =  $file_info['id'];
$FormAction               = $config['siteurl'] . 'do.php?file=' . g('file');
