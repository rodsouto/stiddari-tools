<?php

    function getTotal($data) {
        $total = trim(array_pop(explode("\t", $data)));
        return str_replace(".", "", $total);
    }

    if (!empty($_POST["vision-global"])) {
        $data = explode("\n", trim($_POST["vision-global"]));

        $totalRows = sizeof($data);

        $armas = getTotal($totalRows == 4 ? $data[0] : $data[22]);
        $municion = getTotal($totalRows == 4 ? $data[1] : $data[23]);
        $alcohol = getTotal($totalRows == 4 ? $data[2] : $data[24]);
        $dolares = getTotal($totalRows == 4 ? $data[3] : $data[25]);

        ?>
            <h3>Producción diaria</h3>
            <p>
                Armas: <?php echo number_format($armas*24, 0);?><br />
                Munición: <?php echo number_format($municion*24, 0);?><br />
                Alcohol: <?php echo number_format($alcohol*24, 0);?><br />
                Dolares: <?php echo number_format($dolares*24, 0);?>
            </p>
        <?php
    } else {
?>

    <p class="text-center"><img src="/img/produccion.png" /></p>
    <form method="post">

        <div class="form-group">
            <label class="control-label">Copia y pega aquí debajo la vision global completa</label>
            <textarea class="form-control" rows="10" name="vision-global"></textarea>
        </div>
        <button class="btn btn-primary">Enviar</button>
    </form>
<?php } ?>