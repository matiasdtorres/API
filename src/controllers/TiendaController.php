<?php
require_once __DIR__ . '/../config/db.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TiendaController
{
    public function mesa(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $comensales = $data['max_comensales'];

        $estado_mesa = "esperando";
        $cerrada_mesa = "no"; 

        $db = conectar();
        $stmt = $db->prepare("INSERT INTO mesas (max_comensales, estado, cerrada) VALUES (?, ?, ?)");
        $stmt->execute([$comensales, $estado_mesa, $cerrada_mesa]);

        $response->getBody()->write("Mesa agregada exitosamente");

        return $response;
    }

    public function alta(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $tipo = $data['tipo'];
        $nombre = $data['nombre'];

        $db = conectar();
        $stmt = $db->prepare("SELECT * FROM productos WHERE tipo = ? AND nombre = ?");
        $stmt->execute([$tipo, $nombre]);
        $producto = $stmt->fetch();


        if ($producto)
        {
            $response->getBody()->write("Producto ya existe");
        }
        else
        {
            $stmt = $db->prepare("INSERT INTO productos (tipo, nombre) VALUES (?, ?)");
            $stmt->execute([$tipo, $nombre,]);
            $response->getBody()->write("Producto agregado/actualizado exitosamente");
        }

        return $response;
    }

    public function encuesta(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        if (!isset($data['id_mesa'], $data['id_pedido'], $data['experiencia']))
        {
            $response->getBody()->write("Faltan datos requeridos");
            return $response->withStatus(400);
        }

        $id_mesa = $data['id_mesa'];
        $id_pedido = $data['id_pedido'];
        $experiencia = $data['experiencia'];

        if ($experiencia !== "buena" && $experiencia !== "mala")
        {
            $response->getBody()->write("La experiencia debe ser 'buena' o 'mala'");
            return $response->withStatus(400);
        }

        $db = conectar();

        $stmtPedido = $db->prepare("SELECT * FROM pedidos WHERE id_mesa = ? AND id_pedido = ?");
        $stmtPedido->execute([$id_mesa, $id_pedido]);
        $pedido = $stmtPedido->fetch();

        if (!$pedido)
        {
            $response->getBody()->write("El id_mesa o id_pedido no existen en la tabla de pedidos");
            return $response->withStatus(404);
        }

        $stmtEncuesta = $db->prepare("INSERT INTO encuestas (id_mesa, id_pedido, experiencia) VALUES (?, ?, ?)");
        $stmtEncuesta->execute([$id_mesa, $id_pedido, $experiencia]);

        $response->getBody()->write("Encuesta registrada correctamente");

        return $response;
    }

    public function mejoresExperiencias(Request $request, Response $response, $args)
    {
        $db = conectar();

        $stmt = $db->prepare("SELECT * FROM encuestas WHERE experiencia = 'buena'");

        $stmt->execute();

        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pedidos))
        {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron buenas experiencias. a mejorar pa']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function topMesa(Request $request, Response $response, $args)
    {
        $db = conectar();

        $stmt = $db->prepare("SELECT id_mesa, COUNT(*) AS cantidad_pedidos 
                              FROM pedidos 
                              GROUP BY id_mesa 
                              ORDER BY cantidad_pedidos DESC 
                              LIMIT 1");

        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron pedidos']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($resultado));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
