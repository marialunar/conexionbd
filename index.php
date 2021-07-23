<?php
    // Archivo de Configuracion de conexion inicial
    require_once ('config.php');    // Archivo de Configuracion

    // Constante SQL para crear la tabla TRANSF
    $_TABLA_TRANSF = "CREATE TABLE IF NOT EXISTS CRT.TRANSF (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        origen VARCHAR(30) NOT NULL,
        destino VARCHAR(30) NOT NULL,  
        tablas VARCHAR(30) NOT NULL
      )";

    // Constante SQL para crear la tabla LOG
    $_TABLA_LOG = "CREATE TABLE IF NOT EXISTS CRT.LOG (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        fechahora VARCHAR(30) NOT NULL,
        tabla VARCHAR(30) NOT NULL,
        status VARCHAR(30) NOT NULL,
        detalle JSON NOT NULL
    )";

	
    // Comprobamos que los datos de conexion no esten vacios
    if ( trim($_host) === "" ||  trim($_puerto) === "" || trim($_usuario) === "" ) {
        echo ("Parametros de Conexion al Servidor MySQL incompletos o vacios, verifique el archivo de configuracion config.php\n");
        exit (0);
    }

    // Continuamos con la conexion al Servidor MySQL
    // En caso que exista un error y no permita la conexion desde una ip distinta
    // a la del servidor MySQL Comentar bind-address = 127.0.0.1 en mysqld.cnf.
    // Si el usuario no tiene permisos o la ip no tiene permisos hacer lo siguiente
    // Para el usuario que esta usando para conectar a la base de datos
    // 1- CREATE USER 'usuario'@'%' IDENTIFIED BY 'contraseña';
    // 1- GRANT ALL ON *.* TO 'usuario'@'%';

    $conn = new mysqli($_host.':'.$_puerto, $_usuario, $_pass);
            
    //Verificamos si existe algun error en la conexion
    if ($conn->connect_error) {
        echo ("ERROR-> ". $conn->connect_error);
        exit (0);
    }

    // Comprobamos que exista la BD de control CRT
    $control = $conn->query ("SHOW DATABASES LIKE 'CRT' ");
    
    // Verificamos que no exista algun error en la consulta
    if ( $control === false ) {
        echo ("Error al consultar las bases de datos existentes\n");
        exit (0);
    }

	
    // Si no existe la BD CRT creamos la BD y las tablas
    if ( $control->num_rows === 0 ) {
        echo ('BD de Control no existe, Creando la Base de Datos...');
        $control->close();

        // CREATE DATABASE
        $control = $conn->query ("CREATE DATABASE CRT");
        
        // Si existe algun error imprimimos ERROR y salimos
        if ( $control !== true ) {
            echo ("ERROR\n");
            exit (0);
        }

        // Si la creacion de la BD fue exitosa imprimimos OK y continuamos
        echo ("OK\n");

        // Creamos la Tabla TRANSF = Transferencia, donde se configura
        // La Base de Datos y las Tablas a transferir
        // Si se produce algun error mostramos el error y salimos.
        echo ('Creando Tabla TRANSF...');
        $control = $conn->query ($_TABLA_TRANSF);
        if ( $control === false ) {
            echo ("ERROR\n");
            exit (0);
        }
        
        // Si la creacion de la Tabla fue existosa imprimimos OK y continuamos
        echo ("OK\n");

        // Creamos la Tabla LOG
        // Si se produce algun error mostramos el error y salimos.
        echo ('Creando Tabla LOG...');
        $control = $conn->query ($_TABLA_LOG);
        if ( $control === false ) {
            echo ("ERROR\n");
            exit (0);
        }

        // Si la creacion de la Tabla fue existosa imprimimos OK y continuamos
        echo ("OK\n");

        // Finalizo la creacion de la Base de Datos CRT
        // Continuamos con la Transferencia de BD
    }

	
    // Consultamos la tabla TRANSF 
    $control = $conn->query ("SELECT * FROM CRT.TRANSF");

    // Verificamos que no exista algun error en la consulta
    if ( $control === false ) {
        echo ("Error al consultar la Tabla TRANSF\n");
        exit (0);
    }

    // Si la Tabla esta vacia o no existe datos de transferencia salimos
    if ( $control->num_rows === 0 ) {
        echo ("Tabla de control TRANSF vacia, edite la tabla y agregue la base de datos y las tablas (separadas por coma) que se van a transferir\n");
        echo ("INSERT INTO TRANSF (origen, destino, tablas) VALUES ('BD_origen','BD_destino','tabla1,tabla2,tabla3')\n");
        exit (0);
    }

    // Por cada Fila en la tabla CRT.TRANSF
    while ( $row = $control->fetch_assoc() ) {

        // Verificamos que la base de datos Origen y destino no este en blanco
        if ( !empty($row['origen']) || !empty($row['destino']) ) {
            
            // Si las tablas a copiar estan en blanco o con un * entonces transferimos todas las tablas de la BD Origen
            if ( empty($row['tablas']) || $row['tablas'] === '*' ) {
                
                // Consultamos todas las tablas de la Base de Datos Origen
                $tmp = $conn->query ("SHOW TABLES FROM ".$row['origen']);
                
                // Si no existe error 
                if ( $tmp !== false ) {
                    //si existen tablas en la base de datos Origen
                    if ( $tmp->num_rows > 0 ) {
                        // Mientras existan datos los copiamos a un array
                        while( $tb = $tmp->fetch_array() ) {
                            // Array con las tablas a transferir
                            $tablas[] = $tb[0];
                        }
                        // Cerramos y liberamos
                        $tmp->close();
                    }
                    else {
                        // Guardamos el WARNING en la TABLA LOG
                        Insertlog($conn,$row['origen'],'WARNING', 'La BD Origen '.$row['origen'].' No contiene Tablas');
                    }
                }
                else {
                    // Guardamos el ERROR en la TABLA LOG
                    Insertlog($conn,$row['origen'],'ERROR', 'No Existe la BD Origen '.$row['origen']);
                    
                    // Continuamos con la proxima Fila en la tabla CRT.TRANSF
                    continue;
                }
            }
            
            else {
                // Convertimos las tablas a transferir separadas por comas(,) a un array
                // Array con las tablas a transferir
                $tablas = explode(',', $row['tablas']);
            }

            // Creamos la BD Destino si no existe
            $tmp = $conn->query ("CREATE DATABASE IF NOT EXISTS ".$row['destino']);

            // Si existe algun error 
            if ( $tmp === false ) {
                // Guardamos el ERROR en la TABLA LOG
                Insertlog($conn,$tdestino,'ERROR', 'No se pudo crear la BD Destino '.$row['destino']);
            }
            else {
                // Por cada Tabla en el array de tablas hacemos la transferencia
                foreach( $tablas as $tdestino ) {

                    // Imprimimos mensaje de transferencia
                    echo ("TRANSFIRIENDO ".$row['origen'].".".$tdestino."..." );
                    
                    // Guardamos el tiempo de inicio de la transferencia
                    $inicio = microtime(TRUE);

                    // Transfiere los datos de A a B
                    $tmp = $conn->query ("CREATE TABLE IF NOT EXISTS ".$row['destino'].".".$tdestino." (SELECT * FROM ".$row['origen'].".".$tdestino.")" );
                    
                    // Guardamos el tiempo de finalizacion de la transferenbcia
                    $fin = microtime(TRUE);
                    
                    // Si existe algun error
                    if ( $tmp === false ) {
                        // Mostramos el Mensaje
                        echo ("ERROR\n");
                        // Guardamos el ERROR en la TABLA LOG
                        Insertlog($conn,$tdestino,'ERROR', 'No se pudo Transferir '.$row['destino'].'.'.$tdestino);
                    }
                    else { // Si no existe error 
                        // Calculamos el tiempo de ejecucion de la transferencia y
                        // Convertimos a formato de Hora:Minutos:Segundos
                        $tiempo = date("H:i:s",($fin - $inicio));
                        // Imprimimos el Mensaje
                        echo ("OK ($conn->affected_rows) REGISTROS $tiempo Segundos\n");
                        // Guardamos el Registro en la Tabla LOG
                        Insertlog($conn,$tdestino,'SUCCESS', $tiempo );
                    }
                }
            }
        }
        else
            echo ("Base de datos de Origen o Destino vacia. Origen = {$row['origen']}, Destino = {$row['destino']}");
    }

    echo ("Transferencia Finalizada, Consulte la tabla CRT.LOG para saber mas detalles\n");

    // Funcion que guarda los Logs en la tabla CRT.LOG
    function Insertlog ($conn, $tabla, $status, $detalles) {
        $hora = date("d-m-Y H:i:s");

        if ( $status === "ERROR" ) {
            $detalles = '{"detalles":{ "error":"'.$detalles.'" }}';
        }
        else if ( $status === "WARNING" ) {
            $detalles = '{"detalles":{ "warning":"'.$detalles.'" }}';
        }
        else {
            $detalles = '{"detalles":{ "registros":"'.$conn->affected_rows.'","tiempo":"'.$detalles.'" }}';
        }
        
        $conn->query ("INSERT INTO CRT.LOG (fechahora, tabla, status, detalle) VALUES ('$hora','$tabla','$status','$detalles')" );
    }

    // FIN
?>