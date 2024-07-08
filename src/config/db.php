<?php
function conectar()
{
    try
    {
        $host = 'localhost';
        $db = 'api';
        $user = 'root';
        $pass = '';

        $conexion = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $conexion;
    }
    catch (PDOException $ex)
    {
        echo "Error al conectar a la base de datos: " . $ex->getMessage() . "<br>";
        return null;
    }
}
