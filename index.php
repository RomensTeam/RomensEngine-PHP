<?
$time_start = microtime(true);
define('VERSION', '0.1');

if (version_compare(phpversion(), '5.3.0', '<') == true){
    exit('PHP5.3 Only');
}
include 'config.php';
include 'core.php';
if(TEST_MODE){
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    printf($romens->lang['test_time_script'],$time);
    print_var($_SERVER);
}
