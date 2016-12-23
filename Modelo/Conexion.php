<?php
/*Definición de la clase conexión: */
class Conexion
{
  /*Variables de conexión: */
  private $USER = 'root';
  private $PASS = '';
  private $URL = "mysql:host=localhost";
  private $Conector;
  /*Instancia singleton: */
  private static $Instancia;

  /*Método constructor: Inicializa la variable Conector en null*/
  private function __construct()
  {
    $this->Conector = null;
  }

  /*Funcion: getInstance
  Tipo de retorno: Conexion (Instancia de clase)
  Devuelve una única instancia de esta clase que permite acceder a la base de datos y manipularla
  */
  public static function getInstance()
  {
    if (!self::$Instancia instanceof self) {
      self::$Instancia = new self;
    }
    return self::$Instancia;
  }

  /*Función: Conectar
  Tipo de retorno: PDO Object
  Devuelve un objeto PDO que contiene la conexión directa a la base de datos.
  Sirve como intermediario de la conexión.
  */
  public function Conectar()
  {
    try{
      if (!isset($this->Conector)) {
        $this->Conector = new PDO($this->URL,$this->USER,$this->PASS);
      }
    }catch(Exception $e){
      die("Error al intentar conectar con la base de datos: $e");
    }
    return $this->Conector;
  }

  /*Método: Desconectar
  Finaliza la conexión actual existente con la base de datos
  */
  public function Desconectar()
  {
    $this->Conector = null;
  }
}
?>
