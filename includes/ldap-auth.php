<?php
//error_reporting(0);

//force HTTPS
if($_SERVER['HTTPS'] != "on")
{
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

//return JSON
header("Content-type: text/javascript", 1);
$ret = "no credentials";

session_start();

if(isset($_SESSION['user']))
{
    unset($_SESSION['user']);
    session_destroy();
    session_start();
}

$_SESSION['user'] = null;

if(isset($_REQUEST['un']) && !empty($_REQUEST['un']))
{
    
    $un = trim(stripslashes($_REQUEST['un']));
    $pw = trim(stripslashes($_REQUEST['pw']));
    
    if(login($un, $pw))
    {
        $ret = "authenticated";
        
        $_SESSION['user'] = $un;
    }
    else {
        $ret = "invalid credentials";
    }
}

echo "authCallback(" . json_encode(array("status"=>$ret, "reqId" => $_REQUEST['reqId'])) . ")";

/*
 * Help Functions
 */

//Login Function
function login($username, $password) {
    
    $authenticated = false;
    $dn = 'uid=' . $username . ',ou=people,dc=rit,dc=edu';
    $domain = 'ldaps://ldap.rit.edu';
    $connection = ldap_connect($domain);
    if($connection && !empty($password)) {
        $authenticated = @ldap_bind($connection, $dn, $password);
        @ldap_close($connection);
    }
    
    return $authenticated;
}

?>