<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/usuarios', function (RouteCollectorProxy $group)
{
    $group->post('/registro', 'UsuariosController:registro');
    $group->post('/login', 'UsuariosController:login');
    $group->get('/datos', 'UsuariosController:datos');
});
