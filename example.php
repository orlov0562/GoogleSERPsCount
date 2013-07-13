<?php

    require_once('GoogleSERPsCount.class.php');

    $site = 'www.microsoft.com';

    $gr_config = array(
        'cache_allow'   => TRUE,
        'cache_folder'  => dirname(__FILE__).'/cache/',
    );

    $gr = new \ua\cv\orlov\GoogleSERPsCount( $gr_config );
    $res = $gr->get_res_count($site);

    echo '<pre>';

    print_r($res);

    echo '<hr>';

    print_r($gr->get_errors());

    echo '</pre>';    