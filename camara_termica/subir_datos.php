<?php
 function conectarBD(){ 
            $server = "localhost";
            $usuario = "root";
            $pass = "";
            $BD = "camara_termica";
            //variable que guarda la conexión de la base de datos
            $conexion = mysqli_connect($server, $usuario, $pass, $BD); 
            //Comprobamos si la conexión ha tenido exito
            if(!$conexion){ 
               echo 'Ha sucedido un error inexperado en la conexion de la base de datos<br>'; 
            } 
            //devolvemos el objeto de conexión para usarlo en las consultas  
            return $conexion; 
    }  
    /*Desconectar la conexion a la base de datos*/
    function desconectarBD($conexion){
            //Cierra la conexión y guarda el estado de la operación en una variable
            $close = mysqli_close($conexion); 
            //Comprobamos si se ha cerrado la conexión correctamente
            if(!$close){  
               echo 'Ha sucedido un error inexperado en la desconexion de la base de datos<br>'; 
            }    
            //devuelve el estado del cierre de conexión
            return $close;         
    }
?>

<?php
	$conexion = conectarBD();
	mysqli_query($conexion,"SET NAMES 'utf8'");
	$direccion= $_POST ['direc'];
	$temperatura = $_POST ['temp'];
	if(isset($_POST['direc'])){  
	mysqli_query($conexion,"INSERT INTO `temperaturas`VALUES (NULL, '".$temperatura."', current_timestamp(), '".$direccion."');");###########
}
	mysqli_close($conexion);
	echo "datos subidos"
?>﻿
