<?php

    if (strpos($_SERVER["REQUEST_URI"], "/batallas") !== false) {
        $page = "batallas";
    } elseif (strpos($_SERVER["REQUEST_URI"], "/produccion") !== false) {
        $page = "produccion";
    } else {
        // default page
        Header("Location: /produccion");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stiddari Tools</title>

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<a href="https://github.com/rodsouto/stiddari-tools"><img style="position: absolute; top: 0; right: 0; border: 0;z-index: 999;" src="https://github-camo.global.ssl.fastly.net/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>

<div class="container">
    <nav class="navbar navbar-default" role="navigation">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Stiddari Tools</a>
            </div>

            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li <?php if ($page == "produccion") echo 'class="active"';?>><a href="/produccion">Calculadora de producción</a></li>
                    <li <?php if ($page == "batallas") echo 'class="active"';?>><a href="/batallas">Compactador de batallas</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    include $page.".php";
    ?>

</div>
</body>
</html>