<?php

$active = true;

$objeto = ctrlCRUDGenerator::getInstance();

# Inicio:
$formularioBasesDeDatos = $objeto->listarBasesDeDatos();
if (empty($_REQUEST)) {
  include_once '../Vista/index.php';
} else {
  # Generar archivo SQL:
  if (isset($_POST['btnGenerar'])) {
    $database = $_POST['txtBaseDatos'];
    $objeto->__SET('Database', $_POST['txtBaseDatos']);
    if (isset($_POST['checkboxTablas'])) {
      # Ejecutar acción:
      $objeto->__SET('Tablas', $_POST['checkboxTablas']);
      $objeto->__SET('nombreArchivo', $_POST['txtNombreArchivo']);
      if (array_key_exists('checkCreate', $_POST))
      $objeto->__SET('isCheckCreate', $_POST['checkCreate']);
      if (array_key_exists('checkRead', $_POST))
      $objeto->__SET('isCheckRead', $_POST['checkRead']);
      if (array_key_exists('checkUpdate', $_POST))
      $objeto->__SET('isCheckUpdate', $_POST['checkUpdate']);
      if (array_key_exists('checkDelete', $_POST))
      $objeto->__SET('isCheckDelete', $_POST['checkDelete']);
      if (array_key_exists('checkList', $_POST))
      $objeto->__SET('isCheckList', $_POST['checkList']);
      $r = $objeto->generarArchivoSQL();
      if ($r) {
        $respuesta = "<h2 class='exito'>Los procedimientos almacenados se han creado exitosamente</h2>";
      } else {
        $respuesta = "<h2 class='error'>Ha ocurrido un error al momento de crear los procedimientos almacenados</h2>";
        $formularioTablas = $objeto->listarTablas();
      }

    } else {
      $respuesta = "<h2 class='error'>No se ha seleccionado ninguna tabla</h2>";
      $formularioTablas = $objeto->listarTablas();
    }
    # Incluir la vista:
    include_once '../Vista/index.php';

    # Listar tablas de una base de datos:
  } else if (isset($_POST['btnListarTablas'])) {
    $objeto->__SET('Database', $_POST['radioBaseDeDatos']);
    $formularioTablas = $objeto->listarTablas();
    $database = $_POST['radioBaseDeDatos'];
    include_once '../Vista/index.php';
  } else {
    header("Location: ctrlCRUDGenerator.php");
  }

}

/*
* Clase ctrlCRUDGenerator:
*/
class ctrlCRUDGenerator
{
  private static $instancia;
  private $CRUDGenerator;
  private $Tablas;
  private $nombreArchivo;
  private $Database;
  private $listaBasesDeDatos;
  private $listaTablas;
  private $isCheckCreate;
  private $isCheckRead;
  private $isCheckUpdate;
  private $isCheckDelete;
  private $isCheckList;

  private function __construct()
  {
    require_once '../Modelo/CRUDGenerator.php';
    $this->CRUDGenerator = CRUDGenerator::getInstance();
    $this->isCheckCreate =false;
    $this->isCheckRead =false;
    $this->isCheckUpdate =false;
    $this->isCheckDelete =false;
    $this->isCheckList =false;
  }

  public static function getInstance()
  {
    if (!self::$instancia instanceof self) {
      self::$instancia = new self;
    }
    return self::$instancia;
  }

  public function listarBasesDeDatos()
  {
    $this->listaBasesDeDatos = $this->CRUDGenerator->ListarDBS();
    $formulario="";
    foreach ($this->listaBasesDeDatos as $bd) {
      $formulario .= "<input required type='radio' value='$bd[0]' name='radioBaseDeDatos' id='$bd[0]'><label for='$bd[0]'>$bd[0]</label><br>";
    }
    return $formulario;
  }

  public function listarTablas()
  {
    $this->CRUDGenerator->__SET('Database', $this->Database);
    $this->listaTablas = $this->CRUDGenerator->ListarTBLS();
    $formulario = "<div>";
    $i = 0;
    foreach ($this->listaTablas as $tabla) {
      if ($i%5==0 && $i!=3 && $i!= 0) {
        $formulario .= "</div><div>";
      }
      $formulario .= "<input class='checkbox' type='checkbox' value='$tabla[0]' name='checkboxTablas[]' id='$tabla[0]'> <label for='$tabla[0]'>$tabla[0]</label><br>";
      $i++;
    }
    return $formulario;
  }

  public function generarArchivoSQL()
  {
    $this->CRUDGenerator->__SET('Database', $this->Database);
    $this->CRUDGenerator->__SET('ListaTablasPersonalizadas', $this->Tablas);
    $this->CRUDGenerator->__SET('nombreArchivo', $this->nombreArchivo);
    $this->CRUDGenerator->__SET('isCheckCreate', $this->isCheckCreate);
    $this->CRUDGenerator->__SET('isCheckRead', $this->isCheckRead);
    $this->CRUDGenerator->__SET('isCheckUpdate', $this->isCheckUpdate);
    $this->CRUDGenerator->__SET('isCheckDelete', $this->isCheckDelete);
    $this->CRUDGenerator->__SET('isCheckList', $this->isCheckList);
    return $this->CRUDGenerator->GenerarArchivoSQL();
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
