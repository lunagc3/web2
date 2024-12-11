<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header('Access-Control-Allow-Methods: POST, GET, PATCH, DELETE');
header("Allow: GET, POST, PATCH, DELETE");

date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    
   return 0;    
}  

spl_autoload_register(
    function ($nombre_clase) {
        include __DIR__.'/'.str_replace('\\', '/', $nombre_clase) . '.php';
    }
);
use \Firebase\JWT\JWT;

require_once '../../backend/config/configjwt.php';

require_once '../../backend/config/persistencia.php';



//----router----

$metodo = strtolower($_SERVER['REQUEST_METHOD']);

$comandos = explode('/', strtolower($_GET['comando']??''));

$funcionNombre = $metodo.ucfirst($comandos[0]);

$parametros = array_slice($comandos, 1);
if (count($parametros) >0 && $metodo == 'get') {
    $funcionNombre = $funcionNombre.'ConParametros';
}
if ($funcionNombre == "postlogin") {
    postLogin();
}
else if (function_exists($funcionNombre)) {
    call_user_func_array($funcionNombre, $parametros);
} else {
    header(' ', true, 400);
    /*
    output(['data' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.']);*/
}

//-------soporte
function output($val, $headerStatus = 200)
{
    header(' ', true, $headerStatus);
    header('Content-Type: application/json');
    print json_encode($val);
    die;
}

function outputError($codigo = 500)
{
    switch ($codigo) {
        case 400:
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad request", true, 400);
            die;
        case 401:
            header($_SERVER["SERVER_PROTOCOL"] . " 401 Unauthorized", true, 401);
            die;
        case 404:
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
            die;
        default:
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error", true, 500);
            die;
            break;
    }
}
//conectar bdd-----
//$link = conectarBDD();

//------autenticar
function autenticar($email, $password)
{
    $link = conectarBDD();
    $email = mysqli_real_escape_string($link, $email);
    $password = mysqli_real_escape_string($link, $password);
    $sql = "SELECT id_usuario, nombre FROM usuarios WHERE email='$email' AND password='$password'";
    $resultado = mysqli_query($link, $sql);
    if ($resultado === false) {
        outputError(500, "Falló la consulta: " . mysqli_error($link));
    }

    $ret = false;    
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $ret = [
            'id_usuario'     => $fila['id_usuario'],
            'nombre' => $fila['nombre'],
        ];
    }
    mysqli_free_result($resultado);
    mysqli_close($link);
    return $ret;
}

//------require login
function requiereLogin()
{
    try {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            throw new Exception("Token requerido", 1);
        }
        list($jwt) = sscanf($headers['Authorization'], 'Bearer %s');
        $decoded = JWT::decode($jwt, JWT_KEY, [JWT_ALG]);
        

    } catch(Exception $e) {

        outputError(401);
    }

    return $decoded;
}
//------api------
/////////////TABLA DE USUARIOS Y FUNCIONALIDADES
//devuelve perfil usuario
function getPerfil()
{
    $payload = requiereLogin();
    output(['id_usuario' => $payload->uid, 'nombre' => $payload->nombre]);
}
//post login ingresando user y contraseña ya loggeados previo
function postLogin()
{
    $loginData = json_decode(file_get_contents("php://input"), true);
    $logged = autenticar($loginData['email'], $loginData['password']);

    if ($logged===false) {
        outputError(401);
    }
    
    $payload = [
        'uid'       => $logged['id_usuario'],
        'nombre'    => $logged['nombre'],
        'exp'       => time() + JWT_EXP,
    ];
    $jwt = JWT::encode($payload, JWT_KEY, JWT_ALG);
    output(['jwt'=>$jwt]);
}
//post usuario nuevo
function postUsers(){
    $dato = json_decode(file_get_contents("php://input"), true);
    $link = conectarBDD();

    $email = mysqli_real_escape_string($link, $dato['email']);
    $nombre = mysqli_real_escape_string($link, $dato['nombre']);
    $password = mysqli_real_escape_string($link, $dato['password']);


    $ret=false;
    if (empty($nombre) || empty($email) || empty($password)) {
        echo "vacio";
            outputError(400, "Todos los campos son obligatorios.". mysqli_error($link));
            return $ret;
        }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        outputError(400, "mail invalido". mysqli_error($link));
            return $ret;
    }
    $stmt = mysqli_prepare($link, "SELECT id_usuario FROM usuarios WHERE email=?");
    mysqli_stmt_bind_param($stmt,"s",$email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res)>0) {
        outputError(400, "mail ya registrado". mysqli_error($link));
        return $ret;
    }

    $stmt = mysqli_prepare($link, "INSERT INTO usuarios(email,nombre,password)
            VALUES (?, ?, ?)"); 

    mysqli_stmt_bind_param($stmt, "sss", $email, $nombre, $password);

    if(mysqli_stmt_execute($stmt)){
        $id_usuario = mysqli_insert_id($link);
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        output(['id_usuario'=>$id_usuario]);
        
         $ret=true;   
    }else{
        outputError(500, "error al registrar datos");
    }
    return $ret;

}
//get usuario con id para buscarlo y mandale msj
function getUsersConParametros($id_usuario){
    $link = conectarBDD();
    $query = "SELECT id_usuario, nombre, email FROM usuarios WHERE id_usuario=$id_usuario";
    $res = mysqli_query($link, $query);
    if ($res && mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);

        mysqli_close($link);
        output(['nombre'=>$user['nombre'], 'email'=>$user['email']]);
    } else {
        outputError(404, "Usuario no encontrado");
    }
}
//get usuarios
function getUsers(){
    $payload = requiereLogin();
    $link = conectarBDD();
    $query = "SELECT id_usuario, nombre, email FROM usuarios";
    $res = mysqli_query($link, $query);
    
    if ($res && mysqli_num_rows($res) > 0) {
        $usuarios = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $usuarios[] = $row;
        }
        mysqli_close($link);
        output($usuarios); 
    } else {
        mysqli_close($link);
        outputError(404, "No se encontraron usuarios");
    }
}
//delete usuario con id 
function deleteUsers($id_usuario){
    $link = conectarBDD();
    $query = "DELETE FROM usuarios WHERE id_usuario = $id_usuario";
    if (mysqli_query($link, $query)) {
        mysqli_close($link);
        output(['message' => 'Usuario eliminado correctamente.']);
    } else {
        outputError(500, "Error al eliminar el usuario");
    }
}

////////TABLA DE CONTENIDOS Y FUNCIONALIDADES:
function postContenido(){
    $link = conectarBDD();
    $dato = json_decode(file_get_contents("php://input"), true);
    $payload = requiereLogin();
    $id_usuario = $payload->uid;
    $ret = false;

    $contenido = $dato['contenido'] ?? '';  
    $image_url = $dato['image_url'] ?? '';
    if (empty($contenido)) {
        outputError(400, 'El contenido de texto es obligatorio');
    }

    if (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
        outputError(400, 'La URL de la imagen no es válida');
    }

    $stmt = mysqli_prepare($link, "INSERT INTO contenido (id_usuario, contenido, image_url) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $id_usuario, $contenido, $image_url);

    if (mysqli_stmt_execute($stmt)) {
        $id_contenido = mysqli_insert_id($link); 
        mysqli_stmt_close($stmt);
        mysqli_close($link);

        output(['id_contenido' => $id_contenido, 'contenido' => $contenido, 'image_url' => $image_url]);
        $ret = true;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        outputError(500, 'Error al insertar el contenido en la base de datos');
    }
    return $ret;
}

//get contenidos
function getContenido(){
    $link = conectarBDD();
    $query = "SELECT * FROM contenido";
    $res = mysqli_query($link, $query);

    $contenidos=[];
    while($row = mysqli_fetch_assoc($res)){
        $contenidos[] = $row;
    }
    mysqli_close($link);
    output($contenidos);

}
//get contenido por id
function getContenidoConParametros($id_contenido){
    $link =conectarBDD();
    $query = "SELECT * FROM contenido WHERE id_contenido = $id_contenido";
    $res = mysqli_query($link, $query);

    if ($res && mysqli_num_rows($res) > 0) {
        $contenido = mysqli_fetch_assoc($res);
        mysqli_close($link);
        output($contenido);
    } else {
        outputError(404, "Contenido no encontrado.");
    }
}
//eliminar contenido
function deleteContenido($id_contenido){
    $link = conectarBDD();
    $query = "DELETE FROM contenido WHERE id_contenido = $id_contenido";
    if (mysqli_query($link, $query)) {
        mysqli_close($link);
        output(['message' => 'Contenido eliminado correctamente']);
    } else {
        outputError(500, "Error al eliminar el contenido");
    }
}

//////////////COMPARTIR CONTENIDO ENTRE USUARIOS:
function postEnviarContenido(){
    $link = conectarBDD();
    $dato = json_decode(file_get_contents("php://input"), true);

    // Obtener el token JWT de las cabeceras de la solicitud
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        outputError(401, "Token no proporcionado.");
        return false;
    }

    // Obtener el token desde la cabecera "Authorization"
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);

    // Decodificar y validar el token JWT
    try {
        $decoded = JWT::decode($jwt, JWT_KEY, [JWT_ALG]);  // JWT::decode es la función que decodifica el token
        $id_emisor = $decoded->uid;  // Extraer el ID del usuario del payload
    } catch (Exception $e) {
        outputError(401, "Token inválido: " . $e->getMessage());
        return false;
    }
    
    $ret=false;
    $id_contenido = (int)$dato['id_contenido'];
    $receptor_email = mysqli_real_escape_string($link, $dato['receptor_email']);
    if (empty($id_contenido) || empty($receptor_email)) {
        outputError(400, "El contenido y el receptor son requeridos");
        return $ret;
    }
    //encuentro al receptor
    $query_receptor = "SELECT id_usuario FROM usuarios WHERE email = '$receptor_email'";
    $res_receptor = mysqli_query($link, $query_receptor);
    
    if (mysqli_num_rows($res_receptor) == 0) {
        outputError(404, "Receptor no encontrado.");
        return;
    }
    $receptor_data = mysqli_fetch_assoc($res_receptor);
    $id_receptor = $receptor_data['id_usuario'];
    //encuentro al contenido
    $query_contenido = "SELECT id_contenido FROM contenido WHERE id_contenido = ?";
    $stmt = mysqli_prepare($link, $query_contenido);
    mysqli_stmt_bind_param($stmt, "i", $id_contenido);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        outputError(404, "Contenido no encontrado.");
        return;
    }
    mysqli_stmt_close($stmt);
    //posteo el contenido
    $query_insert = "INSERT INTO enviar_contenido (id_emisor, id_receptor, id_contenido) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($link, $query_insert);
    //if (empty($usuario->id_usuario)) {
    // Handle the error, e.g., display an error message or redirect to a login page
    //die("User ID is missing.");
//}

    mysqli_stmt_bind_param($stmt, "iii", $id_emisor, $id_receptor, $id_contenido);
    
    if (mysqli_stmt_execute($stmt)) {
        $id_enviar_contenido = mysqli_insert_id($link);
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        
        output(['id_enviar_contenido' => $id_enviar_contenido]);
        $ret=true;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        outputError(500, 'Error al enviar el contenido.');
    }
    return $ret;
}


?>