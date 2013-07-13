Class: GoogleSERPsCount
Description: Class for retrieve count of Google SERPs for specified domain
Author: Vitaliy S. Orlov, orlov056@gmail.com, http://www.orlov.cv.ua

Usage:
~~~~~~~~~~~~~~~~~~~~~~~~

<?php
   require_once('GoogleSERPsCount.class.php');
   $gr = new \ua\cv\orlov\GoogleSERPsCount();
   echo '<pre>';
   print_r( $gr->get_res_count('www.microsoft.com') );
   echo '<hr>';
   print_r( $gr->get_errors() );
   echo '</pre>';
?>

Output:
~~~~~~~~~~~~~~~~~~~~~~~~

Array
(
    [all] => 11000000
    [primary] => 631000
    [supplement] => 10369000
)

---------------------------------------------

Array
(
)
   
