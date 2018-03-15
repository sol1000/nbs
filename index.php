<?php
header('Access-Control-Allow-Origin: *');

require_once __DIR__.'/vendor/autoload.php';

use Sung\Mvc\Controller;
use Sung\Mvc\Web;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

require_once __DIR__.'/config/config.php';
require_once __DIR__.'/src/lib/check_database.php';

$controller = Web::getArg('c');
$action = Web::getArg('a');

$controller_file = __DIR__.'/src/controllers/'.$controller.'_controller.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    $show_error = false;

    $controller_class = Web::getClassName($controller);
    $controller_class .= 'Controller';

    if (class_exists($controller_class)) {
        eval('$ctrl = new '.$controller_class.'();');

        $result = $ctrl->runAction($action);
        if ($result !== true) {
            Controller::StaticError('INIT_ERRORS', $result);
        }
    }else {
        Controller::StaticError('INIT_ERRORS', 'ERROR_NO_CONTROLLER_CLASS');
    }
}else {
    Controller::StaticError('INIT_ERRORS', 'ERROR_NO_CONTROLLER_FILE');
}
