<?php
require_once __DIR__ . '/../config/db.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VentasController
{
    public function alta(Request $request, Response $response, $args)
    {

        $data = $request->getParsedBody();
        $empleado = $data['empleado_a_cargo'];
        
        // Conectar a la base de datos
        $db = conectar();

        // Verificar si el usuario existe
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$empleado]);
        $usuario = $stmt->fetch();

        if (!$usuario)
        {
            $response->getBody()->write("El usuario '{$empleado}' no existe");
            return $response;
        }

        $id_mesa = $data['id_mesa'];
        $nombre_producto = $data['producto'];
        $cantidad = $data['cantidad'];
        $imagen = $request->getUploadedFiles()['foto'];

        $db = conectar();

        $stmt = $db->prepare("SELECT * FROM productos WHERE nombre = ?");
        $stmt->execute([$nombre_producto]);
        $producto = $stmt->fetch();

        // Verifica que exista y sino crea la ruta de la imagen si no existe
        $rutaCarpeta = __DIR__ . '/../FotosDeMesas/2024';
        if (!is_dir($rutaCarpeta))
        {
            mkdir($rutaCarpeta, 0777, true);
        }

        $fecha = date('d-m-Y_H_i_s');
        $nombreImagen = "mesa-{$id_mesa}_producto-{$nombre_producto}_cantidad-{$cantidad}_" . "_{$fecha}.jpg";
        $rutaImagen = $rutaCarpeta . "/{$nombreImagen}";

        try
        {
            $imagen->moveTo($rutaImagen);
        }
        catch (Exception $e)
        {
            error_log("Error al mover la imagen: " . $e->getMessage());
        }

        $rutaImagenRelativa = "/FotosDeMesas/2024/$nombreImagen";

        if ($producto)
        {
            $estadodefault = "comanda recibida";

            $stmt = $db->prepare("INSERT INTO pedidos (id_mesa, empleado_a_cargo, producto, cantidad, tiempo_preparacion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_mesa, $empleado, $nombre_producto, $cantidad, $estadodefault]);


            $response->getBody()->write("pedido registrado exitosamente");
        }
        else
        {
            $response->getBody()->write("el producto no existe");
        }
        return $response;
    }

    public function modificar(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id_pedido = $data['id_pedido'];
        $empleado = $data['empleado_a_cargo'];
        $tiempo_preparacion = $data['tiempo_preparacion'];

        $db = conectar();
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE empleado_a_cargo = ?");
        $stmt->execute([$empleado]);
        $venta = $stmt->fetch();

        if ($venta)
        {
            $stmt = $db->prepare("UPDATE pedidos SET tiempo_preparacion = ? WHERE id_pedido = ?");
            $stmt->execute([$tiempo_preparacion, $id_pedido]);

            $response->getBody()->write(json_encode(["mensaje" => "Pedido modificado exitosamente"]));
        }
        else
        {
            $response->getBody()->write(json_encode(["error" => "No existe un pedido con ese id"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function estadoPedido(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $id_mesa = isset($params['id_mesa']) ? $params['id_mesa'] : null;
        $id_pedido = isset($params['id_pedido']) ? $params['id_pedido'] : null;

        if (!$id_mesa || !$id_pedido)
        {
            $response->getBody()->write(json_encode(['error' => 'Falta id_mesa o id_pedido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = conectar();

        $stmt = $db->prepare("SELECT tiempo_preparacion FROM pedidos WHERE id_mesa = ? AND id_pedido = ?");
        $stmt->execute([$id_mesa, $id_pedido]);

        // Verifico si se encontro un pedido
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($ventas))
        {
            $response->getBody()->write(json_encode(['error' => 'Mesa o Pedido no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($ventas));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listado(Request $request, Response $response, $args)
    {
        $db = conectar();

        $stmt = $db->prepare("SELECT id_pedido, tiempo_preparacion FROM pedidos");

        $stmt->execute();

        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pedidos))
        {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron pedidos']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function pedidosPendientes(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $empleado_a_cargo = isset($params['empleado_a_cargo']) ? $params['empleado_a_cargo'] : null;

        if (!$empleado_a_cargo)
        {
            $response->getBody()->write(json_encode(['error' => 'Falta el empleado a cargo']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = conectar();

        $stmt = $db->prepare("SELECT id_pedido, producto, tiempo_preparacion FROM pedidos WHERE empleado_a_cargo = ? AND tiempo_preparacion = 'en preparacion'");
        $stmt->execute([$empleado_a_cargo]);

        // Verifico si se encontro algún pedido
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($pedidos))
        {
            $response->getBody()->write(json_encode(['error' => 'Empleado o pedido "en preparacion" no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function pedidoListo(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $id_mesa = isset($params['id_mesa']) ? $params['id_mesa'] : null;

        if (!$id_mesa)
        {
            $response->getBody()->write(json_encode(['error' => 'Falta el id mesa o pedido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $db = conectar();

        $stmt = $db->prepare("SELECT id_pedido, tiempo_preparacion FROM pedidos WHERE id_mesa = ?");
        $stmt->execute([$id_mesa]);

        // Verifico si se encontro algun pedido
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($pedidos))
        {
            $response->getBody()->write(json_encode(['error' => 'mesa o pedido "listo para servir" no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function estadoMesa(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id_mesa = $data['id_mesa'];
        $estadoMesa = $data['estado'];

        $db = conectar();
        $stmt = $db->prepare("SELECT numero FROM mesas WHERE numero = ?");
        $stmt->execute([$id_mesa]);
        $venta = $stmt->fetch();

        if ($venta)
        {
            $cuenta = "a cobrar";

            $stmt = $db->prepare("UPDATE mesas SET estado = ?, cuenta = ? WHERE numero = ?");
            $stmt->execute([$estadoMesa, $cuenta, $id_mesa]);

            $response->getBody()->write(json_encode(["mensaje" => "Estado de mesa modificado exitosamente"]));
        }
        else
        {
            $response->getBody()->write(json_encode(["error" => "No existe una mesa con ese id"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listadoMesas(Request $request, Response $response, $args)
    {
        $db = conectar();

        $stmt = $db->prepare("SELECT numero, estado, cuenta, cerrada FROM mesas");

        $stmt->execute();

        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pedidos))
        {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron mesas']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function cobrarMesa(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id_mesa = $data['id_mesa'];
        $cuenta = $data['cuenta'];

        $db = conectar();
        $stmt = $db->prepare("SELECT numero FROM mesas WHERE numero = ?");
        $stmt->execute([$id_mesa]);
        $venta = $stmt->fetch();

        if ($venta)
        {

            $stmt = $db->prepare("UPDATE mesas SET cuenta = ? WHERE numero = ?");
            $stmt->execute([$cuenta, $id_mesa]);

            $response->getBody()->write(json_encode(["mensaje" => "Estado de cuenta modificado exitosamente"]));
        }
        else
        {
            $response->getBody()->write(json_encode(["error" => "No existe una mesa con ese id"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function cerrarMesa(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id_mesa = $data['id_mesa'];
        $cerrada = $data['cerrada'];

        $db = conectar();
        $stmt = $db->prepare("SELECT numero FROM mesas WHERE numero = ?");
        $stmt->execute([$id_mesa]);
        $venta = $stmt->fetch();

        if ($venta)
        {
            $estado_mesa_cobrada = "libre";

            $stmt = $db->prepare("UPDATE mesas SET estado = ?, cerrada = ? WHERE numero = ?");
            $stmt->execute([$estado_mesa_cobrada, $cerrada, $id_mesa]);

            $response->getBody()->write(json_encode(["mensaje" => "Mesa cerrada exitosamente"]));
        }
        else
        {
            $response->getBody()->write(json_encode(["error" => "No existe una mesa con ese id"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function pedidosempleado(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $empleado = $params['empleado_a_cargo'];
    
        $db = conectar();
    
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$empleado]);
        $usuario = $stmt->fetch();
    
        if (!$usuario)
        {
            // Si el usuario no existe en la tabla usuarios
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    
        // Si el usuario existe, buscar los pedidos asociados
        $stmt = $db->prepare("SELECT id_pedido FROM pedidos WHERE empleado_a_cargo = ?");
        $stmt->execute([$empleado]);
        $ventas = $stmt->fetchAll();
    
        if (empty($ventas))
        {
            $response->getBody()->write(json_encode(['mensaje' => 'No hay pedidos para este empleado']));
        }
        else
        {
            $response->getBody()->write(json_encode($ventas));
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function descargar(Request $request, Response $response, $args)
    {
        $db = conectar();
        $stmt = $db->query("SELECT * FROM encuestas_");
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "encuestas_" . date('YmdHis') . ".csv";
        $file = fopen('php://temp', 'w');
        fputcsv($file, array_keys($ventas[0]));
        foreach ($ventas as $venta)
        {
            fputcsv($file, $venta);
        }
        rewind($file);

        $response = $response->withHeader('Content-Type', 'text/csv')
                             ->withHeader('Content-Disposition', "attachment; filename={$filename}");
        $response->getBody()->write(stream_get_contents($file));
        fclose($file);

        return $response;
    }
}
