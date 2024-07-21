<?php

function LogAuditoria($usuario)
{
    try
    {
        $db = conectar();

        $fecha = new DateTime("now", new DateTimeZone('America/Argentina/Buenos_Aires'));
        $fecha_formateada = $fecha->format("d-m-Y");
        $hora_formateada = $fecha->format("H:i:s");

        $consulta = "INSERT INTO log_auditoria (usuario, hora, fecha) VALUES (:usuario, :hora, :fecha)";
        
        $stmt = $db->prepare($consulta);
        $stmt->execute([
            ':usuario' => $usuario,
            ':fecha' => $fecha_formateada,
            ':hora' => $hora_formateada
        ]);
    }
    catch (Exception $e)
    {
        return ["error" => "Error: " . $e->getMessage()];
    }

    return null;
}
?>