# conexionbd
Programa en PHP que transfiere el contenido de una tabla a otra
Considere que existen dos bases de datos A y B, realice un programa en el
lenguaje de su preferencia que transfiera el contenidos de las tablas de A a B, en
casos de no existir las tablas en B, deben ser creadas en B.
El planteamiento de transferencia es dinámico, es decir en el sistema se pueden
configurar las tablas a transferir.
a) la configuración de estos detalles de la transferencia se creará en una base de
datos de control CRT.
b) se debe logear en CRT la transferencia de cada tabla: cantidad de registros
insertado y tiempo transcurrido durante la transferencia. La estructura la tabla log
seria la siguiente: log(id, fechahora,tabla, status, detalle), el campo detalle será
tipo jsonb en el cual usted incorporar las key según la información que logee.
Ejemplo:
1,01-01-2021 02:01, spc001d, success, {registros:2000,
tiempo transcurrido:00:30:01}
1,01-01-2021 02:01, spc001d, error, {“err”: “no se pudo crear
tabla”}

*  Proyecto se ejecuta con Manejador de BD MySql
* Archivo  config. php se encuentra la  Configuracion para la conexion al Servidor Mysql.
* Archivo index.php es el programa de conexion de las Bases de Datos, conexion, creacion de tablas  la
creacion de la Base de Datos de respaldo y su transferencia.


#Contact: marialunar@gmail.com
