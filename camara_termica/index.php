
<?php
date_default_timezone_set('America/Santiago');
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

    //Sentencia SQL
//$sql = "SELECT * FROM `temperaturas`;";
//Array Multidimensional
//$rawdata = getArraySQL($sql);

//Adaptar el tiempo


function temperatura_modulo($modulo,$start,$end){
    $start = strtotime($start);
    $end = strtotime($end);
    $link = conectarBD();
    if ($resultado = mysqli_query($link, "SELECT fecha ,temperatura,`id_sensor` FROM temperaturas JOIN sensores ON sensores.id=temperaturas.id_sensor WHERE camara=".$modulo." AND UNIX_TIMESTAMP(fecha) >".$start." AND UNIX_TIMESTAMP(fecha)<".$end." ORDER BY `fecha` ASC;")) {
       
    }
    
    $cont=0;
    while($fila[$cont]=mysqli_fetch_array($resultado)){

        $cont++;
    }

    for($i=0;$i<count($fila);$i++){
    $time = $fila[$i]["fecha"];
    $date = new DateTime($time, new DateTimeZone('America/Santiago'));
    $fila[$i]["fecha"] = $date->getTimestamp()*1000;
    }

    desconectarBD($link);
    return $fila;
}

function obtener_direcciones($modulo){
    $link = conectarBD();
    if ($resultado = mysqli_query($link, "SELECT id, posicion FROM sensores WHERE camara=".$modulo." ORDER BY posicion ASC;")) {
       
    }
    $cont=0;
    while($fila[$cont]=mysqli_fetch_array($resultado)){
        $cont++;
    }
    desconectarBD($link);
    return $fila;
}

function getdata($modulo,$start,$end){
    $direcciones = obtener_direcciones($modulo);
    $datos = temperatura_modulo($modulo,$start,$end);
    $string= '[]';
    if(count($datos)>1){
    $string= '';
    $string= $string."[{ ";
    $cont_datos=0;
    for($i=0; $i<count($direcciones)-1; ++$i){

        $aux= True;
        $cont=0;
        for($k=0; $k<count($datos)-1; ++$k){
            if(strcmp($datos[$k][2], $direcciones[$i][0])==0){
                    if($aux){
                                    if($i != 0 and $cont_datos>1)
                                    $string= $string. " },
                                                        { ";
                            $string= $string."type: 'line', ";
                            $string= $string."name: 'Sensor ".$direcciones[$i][1]."',";
//                          $string= $string."name: '".$direcciones[$i][0]."',";
                             $string= $string."data: [ ";
                             $aux= False;
                    }
                    $string= $string. "[".$datos[$k]["fecha"].",".$datos[$k][1]."],";
                    $cont= $cont+1;
                    $cont_datos= $cont_datos+1;

            }
        }
        $aux= True;
        if($cont>0){
        $string = substr($string, 0, -1);
        $string= $string. "]";
        }
    }
    $string= $string. "}]";
    }
    echo $string; 
}

function getdata2(){
    echo "
    [{
            type: 'line',
            name: 'USD to EUR',
        data: []
    },
    { 
        type: 'line',
        name: 'USD to CLP',
        data: [[1527618745000,3],[1527619225000,4.8],[1527620066000,11.234],[1527620166000,4.8]]
    }]";

}


?>

<?php 
$start = isset($_POST['start']) ? $_POST['start'] :  date('Y-m-d\TH:i', time() - 60 * 60 * 24);
$end = isset($_POST['end']) ? $_POST['end'] :  date('Y-m-d\TH:i');
$modulo_act = isset($_POST['modulo_act']) ? $_POST['modulo_act'] : 1;
$modulo = isset($_POST['modulo']) ? $_POST['modulo'] : $modulo_act ;
?>

<?php //getdata($modulo,$start,$end); 
        //echo "<br>";
        //getdata2();
//echo $modulo_act;
//echo $end;
?>


<!DOCTYPE html>
<html>
<head>
    <title>CAMARA TERMICA</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
</head>
<body> 

<br>

<div class="container">
<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto">
<script type="text/javascript">
    $.getJSON(
    'https://cdn.rawgit.com/highcharts/highcharts/057b672172ccc6c08fe7dbb27fc17ebca3f5b770/samples/data/usdeur.json',
    function (data) {

        Highcharts.setOptions({
            time: {
                timezoneOffset: 3 * 60
            }
        });

        Highcharts.chart('container', {
            chart: {
                zoomType: 'x',
                type: 'spline'
            },
            title: {
                 <?php echo "text: ' Temperaturas en el modulo ".$modulo."',"; ?>
                
                style: {
                    color: '#333333',
                    fontWeight: 'bold', 
                    fontSize: "32px"
                }
            },
            subtitle: {
                text: document.ontouchstart === undefined ?
                        'Clickear y arrastar para hacer zoom' : 'Apretar el gráfico para aumentar'
            },
            xAxis: {
                type: 'datetime'
            },
            yAxis: {
                title: {
                    text: 'Temperatura °C'
                }
            },
            legend: {
                enabled: true
            },
            plotOptions: {
                area: {
                    fillColor: {
                        linearGradient: {
                            x1: 0,
                            y1: 0,
                            x2: 0,
                            y2: 1
                        },
                        stops: [
                            [0, Highcharts.getOptions().colors[0]],
                            [1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                        ]
                    },
                    marker: {
                        radius: 2
                    },
                    lineWidth: 1,
                    states: {
                        hover: {
                            lineWidth: 1
                        }
                    },
                    threshold: null
                }
            },

            series: <?php getdata($modulo,$start,$end); ?>
        });
    }
);

</script>
</div>
</div>
<div class="container">
    <h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Parametros:</h3>
<form class="form-inline" action="/camara_termica/" method="POST">
  <label for="start" class="mr-sm-2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Fecha inicio:</label>
<div class="form-group row">
  <div class="col-10">
    <input class="form-control" type="datetime-local" value="<?php echo isset($_POST['start']) ? $_POST['start'] :date('Y-m-d\TH:i', time() - 60 * 60 * 24); ?>" id="start" name= "start">
  </div>
</div>
  <label for="end" class="mr-sm-2">&nbspFecha termino:</label>
<div class="form-group row">
  <div class="col-10">
    <input class="form-control" type="datetime-local" value="<?php echo date('Y-m-d\TH:i', time() + 60); ?>"  id="end" name= "end">
  </div>
</div>
<input type="hidden" name="modulo_act" value="<?php echo $modulo; ?>">
<label for="x" class="mr-sm-4">&nbsp Camara:</label>
<div class="form-group row">
  <div class="col-10">
    <select class="form-control" id="x" name= "modulo">
        <option  hidden disabled selected value><?php echo $modulo ?></option>
        <option>1</option>
        <option>2</option>
        <option>3</option>
        <option>4</option>
        <option>5</option>
        <option>6</option>
    </select>
  </div>
</div>
<label for="salto" class="mr-sm-4">&nbsp</label>
  <button type="submit" class="btn btn-primary mb-10">Mostrar grafica</button>
</form>
</div>
</body>
</html>