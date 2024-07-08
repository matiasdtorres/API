<?php
require_once __DIR__ . '/../middleware/AutentificadorJWT.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class ConfirmarPerfil
{
    private $perfiles;

    public function __construct(array $perfiles)
    {
        $this->perfiles = $perfiles;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeader('Authorization');
        if (empty($authHeader))
        {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token no proporcionado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = str_replace('Bearer ', '', $authHeader[0]);

        try
        {
            AutentificadorJWT::verificarToken($token);
            $decoded = AutentificadorJWT::obtenerData($token);

            // Verifica el perfil dado
            if (!in_array($decoded->perfil, $this->perfiles))
            {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Perfil no autorizado']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Accede al usuario desde el token decodificado
            $usuario = $decoded->usuario; // Asumiendo que el payload del token tiene un campo "usuario"
            
            // Puedes hacer algo con el usuario aquí, como agregarlo a los atributos de la solicitud
            $request = $request->withAttribute('usuario', $usuario);
            $request = $request->withAttribute('decoded', $decoded);
            return $handler->handle($request);
        }
        catch (\Exception $e)
        {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token invalido: ' . $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
