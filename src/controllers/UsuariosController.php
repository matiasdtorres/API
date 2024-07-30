<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/log.php';
require_once __DIR__ . '/../middleware/AutentificadorJWT.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsuariosController
{
    public function registro(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $mail = $data['mail'];
        $usuario = $data['usuario'];
        $contraseña = password_hash($data['contraseña'], PASSWORD_DEFAULT);
        $perfil = $data['perfil'];

        $perfilesPermitidos = ["admin", "socio", "mozo", "chef"];
        if (!in_array($perfil, $perfilesPermitidos))
        {
            $response->getBody()->write(json_encode(["error" => "Perfil no válido. Los perfiles permitidos son 'admin', 'socio' o 'mozo'"]));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }

        $db = conectar();

        // Consulta para verificar si el usuario ya existe
        $stmt = $db->prepare("SELECT COUNT(*) as contador FROM usuarios WHERE usuario = ? OR mail = ?");
        $stmt->execute([$usuario, $mail]);
        $resultado = $stmt->fetch();

        if ($resultado['contador'] > 0)
        {
            $response->getBody()->write(json_encode(["error" => "El usuario o mail ya estan registrados"]));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("INSERT INTO usuarios (mail, usuario, contraseña, perfil) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mail, $usuario, $contraseña, $perfil]);

        $response->getBody()->write(json_encode(["mensaje" => "Usuario registrado exitosamente"]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    

    public function login(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $usuario = $data['usuario'];
        $contraseña = $data['contraseña'];
    
        $db = conectar();
    
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $usuario = $stmt->fetch();
    
        if ($usuario && password_verify($contraseña, $usuario['contraseña']))
        {
            $payload = [
                'usuario' => $usuario['usuario'],
                'perfil' => $usuario['perfil'],
                'iat' => time(), // Tiempo de emisión del token
                'exp' => time() + (60 * 60) // Token expira en una hora
            ];
            $token = AutentificadorJWT::crearToken($payload);
            $response->getBody()->write(json_encode(['token' => $token]));

            $log = LogAuditoria($usuario['usuario']);

            return $response->withHeader('Content-Type', 'application/json');
        }
        else
        {
            $response->getBody()->write(json_encode(["error" => "Usuario o contraseña incorrectos"]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function datos(Request $request, Response $response, $args)
    {
        $token = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $token, $matches))
        {
            $token = $matches[1];
        }
        else
        {
            $response->getBody()->write(json_encode(['error' => 'Token no encontrado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try
        {
            $data = AutentificadorJWT::obtenerData($token);
            $response->getBody()->write(json_encode($data));
        }
        catch (Exception $e)
        {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
    
}