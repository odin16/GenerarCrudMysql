<?php

/*
* Clase Asistente gestiona todo el proceso de creación de Procedimientos almacenados
*/
require_once 'Conexion.php';
class CRUDGenerator {

  private static $CONSULTAR_DBS = "SHOW DATABASES";
  private static $CONSULTAR_TABLAS = "SELECT table_name FROM information_schema.tables WHERE table_schema = ?";
  private static $CONSULTAR_CAMPOS = "SELECT column_name, column_type FROM information_schema.columns WHERE table_name = ? AND table_schema = ?";
  private static $ENCABEZADO = "
  -- ******************************************************************************* --
  -- ************** PROCEDIMIENTOS ALMACENADOS DE ? *************** --
  -- ******************************************************************************* --";

  private $Database;
  private $Tablas;
  private $ListaTablasPersonalizadas;
  private $nombreArchivo;

  private $Campos;
  private $Argumentos = "";
  private $ArgumentoEstado = "";
  private $ValueEstado = "";
  private $Values = "";
  private $Columnas = "";
  private $ColumnasValues = "";
  private $idTabla;
  private $ArgumentoIdTabla;

  private static $Conexion;
  private $Ejecutador;
  private $Respuesta;
  private $Lista;
  private $Procedimientos;
  private $msn;
  private static $instancia;

  private $isCheckCreate;
  private $isCheckRead;
  private $isCheckUpdate;
  private $isCheckDelete;
  private $isCheckList;

  /*Metodo constructor: Establece la conexión con la base de datos
  mediante la instancia del singleton
  */
  private function __construct()
  {
    self::$Conexion = Conexion::getInstance();
    $this->Ejecutador = null;
    $this->Respuesta = false;
    $this->msn = false;
    $this->Lista = array();
    $ListaTablasPersonalizadas=array();
  }

  public static function getInstance()
  {
    if (!self::$instancia instanceof self) {
      self::$instancia = new self;
    }
    return self::$instancia;
  }

  /*
  * Esta función consulta todas las bases de datos del sistema
  */
  public function ListarDBS()
  {
    try {
      $this->Ejecutador = self::$Conexion->Conectar()->prepare(self::$CONSULTAR_DBS);
      $this->Ejecutador->execute();
      $this->Lista = $this->Ejecutador->fetchAll(PDO::FETCH_NUM);
    } catch (Exception $e) {
      die("Error al intentar listar las bases de datos".$e);
    }
    return $this->Lista;
  }

  /*
  * Esta función consulta todas las tablas de una base de datos
  */
  public function ListarTBLS()
  {
    try {
      $this->Ejecutador = self::$Conexion->Conectar()->prepare(self::$CONSULTAR_TABLAS);
      $this->Ejecutador->bindParam(1, $this->Database);
      $this->Ejecutador->execute();
      $this->Lista = $this->Ejecutador->fetchAll(PDO::FETCH_NUM);
    } catch (Exception $e) {
      die("Error al intentar listar las bases de datos".$e);
    }
    return $this->Lista;
  }

  /*
  * Esta función consulta todos los campos de la tabla y su tipo de dato
  */
  private function ListarCampos($nombreTabla)
  {
    try {
      $this->Ejecutador = self::$Conexion->Conectar()->prepare(self::$CONSULTAR_CAMPOS);
      $this->Ejecutador->bindParam(1, $nombreTabla);
      $this->Ejecutador->bindParam(2, $this->Database);
      $this->Ejecutador->execute();
      $this->Lista =  $this->Ejecutador->fetchAll(PDO::FETCH_NUM);
    } catch (Exception $e) {
      die("Error al intentar listar las bases de datos".$e);
    }
    return $this->Lista;
  }

  /*
  * Esta funcion genera la estructura básica de los campos de cada tabla
  */
  private function generarEstructuraCampos($Tabla)
  {
    $this->Campos = $this->ListarCampos($Tabla[0]);
    $i=0;
    foreach ($this->Campos as $Campo) {
      $Coma = ($i+1 < sizeof($this->Campos)) ? ", " : "";
      if($i>0){
        if (is_int(strpos(strtolower($Campo[0]), 'estadotabla'))) {
          $this->ArgumentoEstado = "IN $$Campo[0] $Campo[1]";
          $this->ValueEstado .="`$Campo[0]` = $$Campo[0]";
        }
        $this->Argumentos .="IN $$Campo[0] $Campo[1]$Coma";
        $this->Values .="$$Campo[0]$Coma";
        $this->Columnas .="`$Campo[0]`$Coma";
        $this->ColumnasValues .="`$Campo[0]` = $$Campo[0]$Coma";
      } else {
        $this->idTabla="`$Campo[0]`";
        $this->ArgumentoIdTabla="$$Campo[0]";
      }
      $i++;
    }
  }

  /*
  * Esta función genera la estructura básica del CRUD de cada tabla
  */
  private function generarEstructuraCRUD($Tabla)
  {
    # Nombre tabla en mayúsculas y camelcase:
    $TableName = str_replace("tbl_", "", $Tabla[0]);
    $UpperTableName = strtoupper($TableName);
    $CapitalizeTableName = ucwords($TableName);

    # Estructura especial de cambio de estado:
    $CambiarEstado = "";
    if ($this->ArgumentoEstado != "" && $this->ValueEstado != "") {
      $CambiarEstado .= "
      -- ================== CAMBIAR ESTADO $UpperTableName ================= --

      DROP PROCEDURE IF EXISTS spCambiarEstado$CapitalizeTableName;
      DELIMITER !
      CREATE PROCEDURE spCambiarEstado$CapitalizeTableName(IN $this->ArgumentoIdTabla INT, $this->ArgumentoEstado)
      BEGIN
      UPDATE `$Tabla[0]` SET $this->ValueEstado WHERE $this->idTabla = $this->ArgumentoIdTabla;
      END !
      DELIMITER ;";
    }

    # Evitar cambio de estado en modificar:
    $ArgumentosModificar = $this->Argumentos;
    $ValuesModificar = $this->ColumnasValues;
    if ($this->ArgumentoEstado && is_int(strpos($ArgumentosModificar, $this->ArgumentoEstado))) {
      $pos_args = strpos($ArgumentosModificar, $this->ArgumentoEstado);
      $pos_val = strpos($ValuesModificar, $this->ValueEstado);
      $resto_args = '';
      $resto_val = '';
      if ($pos_args == 0) {
        $ArgumentosModificar = str_replace($this->ArgumentoEstado . ',', '', $ArgumentosModificar);
        $ValuesModificar = str_replace($this->ValueEstado . ',', '', $ValuesModificar);
      } else {
        $ArgumentosModificar = str_replace($this->ArgumentoEstado, '', $ArgumentosModificar);
        $ValuesModificar = str_replace($this->ValueEstado, '', $ValuesModificar);
        $resto_args = substr($ArgumentosModificar, $pos_args, strlen($ArgumentosModificar));
        $resto_val = substr($ValuesModificar, $pos_val, strlen($ValuesModificar));
        $ArgumentosModificar = substr($ArgumentosModificar, 0, $pos_args - 2);
        $ArgumentosModificar .= $resto_args;
        $ValuesModificar = substr($ValuesModificar, 0, $pos_val - 2);
        $ValuesModificar .= $resto_val;
      }
    }

    # Validar las partes del CRUD que se quieren generar
    $create = ($this->isCheckCreate) ? "

    -- ================== REGISTRAR $UpperTableName ================= --

    DROP PROCEDURE IF EXISTS spRegistrar$CapitalizeTableName;
    DELIMITER !
    CREATE PROCEDURE spRegistrar$CapitalizeTableName($this->Argumentos)
    BEGIN
    INSERT INTO `$Tabla[0]`($this->Columnas) VALUES ($this->Values);
    END !
    DELIMITER ;

    " : "\0";

    $read = ($this->isCheckRead) ? "

    -- =================== CONSULTAR $UpperTableName ================ --

    DROP PROCEDURE IF EXISTS spConsultar$CapitalizeTableName;
    DELIMITER !
    CREATE PROCEDURE spConsultar$CapitalizeTableName(IN $this->ArgumentoIdTabla INT)
    BEGIN
    SELECT * FROM `$Tabla[0]` WHERE $this->idTabla = $this->ArgumentoIdTabla;
    END !
    DELIMITER ;

    " : "\0";

    $update = ($this->isCheckUpdate) ? "

    -- ================== MODIFICAR $UpperTableName ================= --

    DROP PROCEDURE IF EXISTS spModificar$CapitalizeTableName;
    DELIMITER !
    CREATE PROCEDURE spModificar$CapitalizeTableName(IN $this->ArgumentoIdTabla INT, $ArgumentosModificar)
    BEGIN
    UPDATE `$Tabla[0]` SET $ValuesModificar WHERE $this->idTabla = $this->ArgumentoIdTabla;
    END !
    DELIMITER ;

    " : "\0";

    $list = ($this->isCheckList) ? "

    -- ==================== LISTAR $UpperTableName ================== --

    DROP PROCEDURE IF EXISTS spListar$CapitalizeTableName;
    DELIMITER !
    CREATE PROCEDURE spListar$CapitalizeTableName()
    BEGIN
    SELECT * FROM `$Tabla[0]`;
    END !
    DELIMITER ;

    " : "\0";

    $delete = ($this->isCheckDelete) ? "

    -- =================== ELIMINAR $UpperTableName ================= --

    DROP PROCEDURE IF EXISTS spEliminar$CapitalizeTableName;
    DELIMITER !
    CREATE PROCEDURE spEliminar$CapitalizeTableName(IN $this->ArgumentoIdTabla INT)
    BEGIN
    DELETE FROM `$Tabla[0]` WHERE $this->idTabla = $this->ArgumentoIdTabla;
    END !
    DELIMITER ;

    " : "\0";


    # Estructura de Procedimientos almacenados del CRUD:
    $this->Procedimientos .="

    -- # CRUD $Tabla[0] --
    $create $read $update $list $delete $CambiarEstado

    ";

    $Tabla[0]="";
    $TableName="";
    $UpperTableName="";
    $CapitalizeTableName="";
    $this->Clear();

  }

  # Resetear todo despues de generar el CRUD
  private function Clear()
  {
    $this->Argumentos="";
    $this->Columnas="";
    $this->Values="";
    $this->ArgumentoIdTabla="";
    $this->ColumnasValues="";
    $this->idTabla="";
    $this->ArgumentoIdTabla="";
    $this->ArgumentoEstado = "";
    $this->ValueEstado = "";
  }

  /*
  * Esta función se encarga de crear el script de
  * todos los procedimientos almacenados de la base de datos
  */
  private function generarProcedimientosAlmacenados()
  {
    $this->Tablas = self::ListarTBLS($this->Database);
    self::$ENCABEZADO = str_replace("?", $this->Database, self::$ENCABEZADO);
    $this->Procedimientos .= self::$ENCABEZADO;
    foreach ($this->Tablas as $Tabla) {
      foreach ($this->ListaTablasPersonalizadas as $tbl) {
        if($Tabla[0] == $tbl){
          $this->generarEstructuraCampos($Tabla);
          $this->generarEstructuraCRUD($Tabla);
        }
      }
    }
  }

  /*
  * Esta función crea un archivo SQL donde se guardará
  * el script de los Procedimientos almacenados
  */
  public function GenerarArchivoSQL()
  {
    try {
      $this->generarProcedimientosAlmacenados();
      $Archivo = fopen("$this->nombreArchivo.sql", "c") or
      die("Ha ocurrido un error al momento de crear el archivo SQL");
      fwrite($Archivo,$this->Procedimientos) or die("Error");
      fclose($Archivo);
      $this->Procedimientos="";
      $this->msn = true;
      self::DescargarArchivo();
    } catch (Exception $e) {
      die("Error al intentar generar el archivo SQL".$e);
    }
    return $this->msn;
  }

  /**
  * Generar fichero de descarga
  */
  public function DescargarArchivo() {
    if (file_exists("$this->nombreArchivo.sql")) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.basename("$this->nombreArchivo.sql").'"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize("$this->nombreArchivo.sql"));
      readfile("$this->nombreArchivo.sql");
      unlink("$this->nombreArchivo.sql");
      exit;
    }
  }

  # Método Setter:
  public function __SET($var, $valor) {
    if (property_exists(__CLASS__, $var)) {
      $this->$var = $valor;
    } else {
      echo "* No existe el atributo $var.";
    }
  }

}

?>
