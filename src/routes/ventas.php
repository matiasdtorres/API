<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/ventas', function (RouteCollectorProxy $group)
{
    $group->post('/alta', 'VentasController:alta')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->put('/modificar', 'VentasController:modificar')->add(new ConfirmarPerfil(['admin', 'mozo', 'socio']));
    $group->get('/ingresos', 'VentasController:ingresosTotales')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->get('/estado', 'VentasController:estadoPedido');
    $group->get('/listado', 'VentasController:listado')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->get('/pendientes', 'VentasController:pedidosPendientes')->add(new ConfirmarPerfil(['admin', 'mozo', 'socio']));
    $group->get('/listo', 'VentasController:pedidoListo')->add(new ConfirmarPerfil(['mozo']));
    $group->put('/comiendo', 'VentasController:estadoMesa')->add(new ConfirmarPerfil(['mozo']));
    $group->put('/cobrar', 'VentasController:cobrarMesa')->add(new ConfirmarPerfil(['mozo']));
    $group->put('/cerrar', 'VentasController:cerrarMesa')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->get('/listadomesas', 'VentasController:listadoMesas')->add(new ConfirmarPerfil(['socio']));
    $group->get('/pedidosempleado', 'VentasController:pedidosempleado')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->get('/masvendido', 'VentasController:masVendido')->add(new ConfirmarPerfil(['admin', 'socio']));
    $group->get('/descargar', 'VentasController:descargar')->add(new ConfirmarPerfil(['admin']));
    $group->get('/pdf', 'VentasController:pdf')->add(new ConfirmarPerfil(['admin', 'socio']));
});
