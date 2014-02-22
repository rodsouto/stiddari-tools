<?php

include_once "CombatParser.php";

if (!empty($_POST["reporte"])) {

    $parser = new CombatParser();
    ?>
    <pre><?php echo $parser->getBbCode($_POST["reporte"]);?></pre>
<?php
} else {
    ?>

    <form method="post">

        <div class="form-group">
            <label class="control-label">Copia y pega aquí debajo el reporte de la batalla</label>
            <textarea class="form-control" rows="20" name="reporte"></textarea>
        </div>
        <button class="btn btn-primary">Enviar</button>
    </form>
<?php } ?>