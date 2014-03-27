<?php

include "vendor/autoload.php";
include_once "CombatParser.php";

if (!empty($_POST["reporte"])) {

    $parser = new CombatParser();

    foreach ($parser->getBbCode($_POST["reporte"]) as $lang => $bbcode) {
        echo "<h3>Language: ".strtoupper($lang)."</h3>";
        echo "<pre>$bbcode</pre>";
    }
    ?>
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