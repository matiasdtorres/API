<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/middleware/ConfirmarPerfil.php';
require_once __DIR__ . '/../src/middleware/AutentificadorJWT.php';
require_once __DIR__ . '/../src/controllers/UsuariosController.php';
require_once __DIR__ . '/../src/controllers/TiendaController.php';
require_once __DIR__ . '/../src/controllers/VentasController.php';

require_once __DIR__ . '/creardb.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();


$app->get('/', function (Request $request, Response $response, $args)
{
    $response->getBody()->write("Funciona todo paaaaa");
    return $response;
});

$app->get('/db', function (Request $request, Response $response, $args) {
    try
    {
        $setup = new DatabaseSetup();
        $setup->createDatabase();
        $setup->connectToDatabase();
        $setup->createTables();
        $response->getBody()->write("Base de datos y tablas creadas exitosamente.");
    }
    catch (Exception $e)
    {
        $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
    return $response;
});

$db = new DatabaseSetup();
$db->connectToDatabase();

require_once __DIR__ . '/../src/routes/tienda.php';
require_once __DIR__ . '/../src/routes/ventas.php';
require_once __DIR__ . '/../src/routes/usuarios.php';

$app->run();
