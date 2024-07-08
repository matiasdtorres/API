<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/tienda', function (RouteCollectorProxy $group)
{
    $group->post('/mesa', 'TiendaController:mesa')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->post('/alta', 'TiendaController:alta')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->post('/encuesta', 'TiendaController:encuesta');
    $group->get('/mejores', 'TiendaController:mejoresExperiencias')->add(new ConfirmarPerfil(['socio']));
    $group->get('/top', 'TiendaController:topMesa')->add(new ConfirmarPerfil(['socio']));
});
