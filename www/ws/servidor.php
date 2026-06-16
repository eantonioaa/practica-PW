<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once('lib/nusoap.php');
include('../db.php');

$namespace = "www.sportroute.es";

$server = new soap_server();
$server->soap_defencoding = 'utf-8';
$server->decode_utf8 = false;

$server->configureWSDL("SportRouteWS", $namespace);
$server->wsdl->schemaTargetNamespace = $namespace;

// Definir tipo complejo Usuario
$server->wsdl->addComplexType(
    'Usuario',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'nombre'           => array('name' => 'nombre',           'type' => 'xsd:string'),
        'apellidos'        => array('name' => 'apellidos',        'type' => 'xsd:string'),
        'tipo_actividad'   => array('name' => 'tipo_actividad',   'type' => 'xsd:string'),
    )
);

// Definir tipo array de usuarios
$server->wsdl->addComplexType(
    'ListaUsuarios',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Usuario[]')
    ),
    'tns:Usuario'
);

// Registrar operación
$server->register(
    'ObtenerUsuarios',
    array('nombre' => 'xsd:string', 'apellidos' => 'xsd:string'),
    array('return' => 'tns:ListaUsuarios'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Busca usuarios por nombre y/o apellidos'
);

function ObtenerUsuarios($nombre, $apellidos) {
    global $conexion;

    $where = [];
    if (!empty($nombre)) {
        $nombre = mysqli_real_escape_string($conexion, $nombre);
        $where[] = "u.nombre LIKE '%$nombre%'";
    }
    if (!empty($apellidos)) {
        $apellidos = mysqli_real_escape_string($conexion, $apellidos);
        $where[] = "u.apellidos LIKE '%$apellidos%'";
    }

    $sql = "SELECT u.nombre, u.apellidos, ta.nombre as tipo_actividad
            FROM usuarios u
            LEFT JOIN tipos_actividad ta ON u.id_actividad_preferida = ta.id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $resultado = mysqli_query($conexion, $sql);
    $usuarios = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $usuarios[] = array(
            'nombre'         => $fila['nombre'] ?? '',
            'apellidos'      => $fila['apellidos'] ?? '',
            'tipo_actividad' => $fila['tipo_actividad'] ?? ''
        );
    }

    return $usuarios;
}

$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>