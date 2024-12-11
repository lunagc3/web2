<?php
require_once 'configdb.php';
function conectarBDD(){
    
    $link = mysqli_connect(DBHOST, DBUSER, DBPASS,DBBASE);

    if ($link === false) {
        outputError(500, "Falló la conexión: " . mysqli_connect_error());
    }
    mysqli_set_charset($link, 'utf8');
    echo "Conectado a la bdd";
    return $link;
    
}
echo "persistencia esta ok";

//---cargar bdd
