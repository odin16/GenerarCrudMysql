<?php
if (!isset($active)) {
  header("Location: ../Controlador/ctrlCRUDGenerator.php");
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="../Vista/style.css">
  <title>Asistente</title>
</head>
<body>
  <h2>Seleccione una base de datos: </h2>
  <div class="principalForm">
    <form action="ctrlCRUDGenerator.php" method="post">
      <?=$formularioBasesDeDatos?>
      <br>
      <input type="submit" name="btnListarTablas" value="Ver tablas">
    </form>
  </div>

  <?=isset($respuesta)?$respuesta:""?>

  <?php if (!empty($formularioTablas)) { ?>
    <h2>Tablas presentes en <?=$database?></h2>
    <form action="ctrlCRUDGenerator.php" method="post">
      <div class="input">
        <input  type='hidden' name='txtBaseDatos' value='<?=$database?>'>
        <div><label>Nombre del archivo de salida</label></div>
        <div><input required type="text" name="txtNombreArchivo">
          <input type="submit" name="btnGenerar" value="Generar archivo">
        </div>
        <div><input type="checkbox" id="checkCreate" name="checkCreate"> <label for="checkCreate">Generar Registrar</label></div>
        <div><input type="checkbox" id="checkRead" name="checkRead"> <label for="checkRead">Generar Consultar</label></div>
        <div><input type="checkbox" id="checkUpdate" name="checkUpdate"> <label for="checkUpdate">Generar Modificar</label></div>
        <div><input type="checkbox" id="checkDelete" name="checkDelete"> <label for="checkDelete">Generar Eliminar</label></div>
        <div><input type="checkbox" id="checkList" name="checkList"> <label for="checkList">Generar Listar</label></div>
        <div><input type="checkbox" id="checkAll"> <label for="checkAll">Seleccionar todas las tablas</label></div>
      </div>
      <div class="form">
        <div class="form-center">
          <?=$formularioTablas?>
        </div>
      </div>
    </form>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <?php } ?>
    <script src="../Vista/jquery-1.11.3.min.js"></script>
    <script>
    var bool = true;
    $("#checkAll").click(function() {
      if (bool) {
        $(".checkbox").each(function() {
          $(this).prop("checked","checked");
          console.log("Marqué");
        });
        bool = false;
      } else {
        $(".checkbox").each(function() {
          $(this).prop("checked","");
          console.log("Desmarqué");
        });
        bool = true;
      }
      console.log(bool);
    });
    </script>
  </body>
  </html>
