<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AutentificadorJWT
{
    private static $claveSecreta = 'claveSuperSecreta123';
    private static $algoritmo = 'HS256';

    public static function crearToken($datos)
    {
        $tiempo = time();
        $payload = array(
            'iat' => $tiempo,
            'exp' => $tiempo + (60*60), // Expira en 1 hora el token
            'data' => $datos
        );
        return JWT::encode($payload, self::$claveSecreta, self::$algoritmo);
    }

    public static function verificarToken($token)
    {
        if(empty($token))
        {
            throw new Exception("Token vacío");
        }
        $decoded = JWT::decode($token, new Key(self::$claveSecreta, self::$algoritmo));
        return $decoded;
    }

    public static function obtenerData($token)
    {
        return JWT::decode($token, new Key(self::$claveSecreta, self::$algoritmo))->data;
    }
}
