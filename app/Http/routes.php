<?php

use App\Services\Routes as RoutesManager;

//这里你可以写一些路由覆盖掉RoutesManager的
//.....

$routesManager = new RoutesManager();
$routesManager->admin()->ewww()->www();
// $routesManager->admin()->www();
// $routesManager->www();