<?php

class CombatParser {

    public function getWinner($tropas, $atacante, $defensor) {
        $tropasExtraAtacante = $tropasExtraDefensor = false;

        if (empty($tropas["defensor"])) return $atacante;

        $quedanTropasExtra = function($tropas) {
            foreach ($tropas as $v) {
                if ($v[0] == 0) {
                    // no tenia ninguna tropa de este tipo
                    continue;
                }
                if ($v[0] != $v[1]) {
                    // le quedaron algunas tropas
                    return true;
                }
            }
            return false;
        };
        $tropasExtraAtacante = $quedanTropasExtra($tropas["atacante"]);
        $tropasExtraDefensor = $quedanTropasExtra($tropas["defensor"]);

        if ($tropasExtraAtacante && !$tropasExtraDefensor) {
            return $atacante;
        }

        if (!$tropasExtraAtacante && $tropasExtraDefensor) {
            return $defensor;
        }
        return false;
    }

    function parse($reporte) {
        $data = explode("\n", trim($reporte));

        if(preg_match('/^\d{1,3}:\d{1,3}:\d{1,3}/', $data[0])) {
            unset($data[0]);
        }

        foreach ($data as $k => $d) {
            if(trim($d) == "") {
                unset($data[$k]);
            } else {
                break;
            }
        }

        $data = array_values($data);
        $parsedData = array();
        $i = 0;
        $j = 0;
        foreach($data as $k => $d) {
            if ($k < 3) continue;
            if (trim($d) == "") {
                $j = $j == 0 ? 1 : 0;
                if($j == 0) $i++;
                continue;
            }
            $parsedData[$i][$j][] = $d;
        }

        $recursos = array();
        if (sizeof($parsedData[sizeof($parsedData)-1]) == 1) {
            $recursos = reset(array_pop($parsedData));
        }
        $rondas = sizeof($parsedData);

        $players = array_map(function($i){
            return end(explode("(", $i));
        }, explode(")", $data[0]));

        $tropas = array();
        foreach ($parsedData as $d) {
            foreach ($d[0] as $k => $t) {
                if ($k <= 1) {
                    continue;
                }

                $t = array_values(array_filter(explode("\t", trim($t)), function($i) {
                    return trim($i) != "";
                }));

                if (sizeof($t) == 3) {
                    // tropas defensivas
                    if (!isset($tropas["defensor"][$t[0]])) {
                        $tropas["defensor"][$t[0]] = array($t[1], $t[2]);
                    } else {
                        $tropas["defensor"][$t[0]][1] += $t[2];
                    }
                } else {
                    // tropas de ataque
                    if ($t[1] != 0) {
                        if (!isset($tropas["atacante"][$t[0]])) {
                            $tropas["atacante"][$t[0]] = array($t[1], $t[2]);
                        } else {
                            $tropas["atacante"][$t[0]][1] += $t[2];
                        }
                    }

                    if ($t[3] != 0) {
                        if (!isset($tropas["defensor"][$t[0]])) {
                            $tropas["defensor"][$t[0]] = array($t[3], $t[4]);
                        } else {
                            $tropas["defensor"][$t[0]][1] += $t[4];
                        }
                    }
                }


            }
        }

        array_walk($recursos, function(&$t){$t = explode("\t", trim($t));});

        $params = array(
            "date" => trim($data[1]),
            "rondas" => $rondas,
            "players" => $players,
            "tropas" => $tropas,
            "recursos" => $recursos
        );

        return $params;
    }

    public function getBbCode($reporte) {
        $params = $this->parse($reporte);
        $bbcode = "[b]Combate del día ".$params["date"]."[/b]\nLa batalla duró [b][size=18]".$params["rondas"]."[/size][/b] ronda(s).\n";

        $bbcode .= "\n[b]ATACANTE[/b]\n[b][size=19][color=red]".trim($params["players"][0])."[/color][/size][/b]\n";
        foreach ($params["tropas"]["atacante"] as $tropa => $cantidades) {
            $bbcode .= "$tropa [color=red]".$cantidades[0]."[/color] [color=purple]Muertos: ".$cantidades[1]."[/color]\n";
        }

        /*$bbcode .= "[color=green]([i]Losses: 494.208.000 Weapons, 732.779.000 Ammunition, 308.104.000 Dollar[/i])[/color]
        [b]Chances of Winning: [color=green]80% 20%[/color][/b]";*/

        $bbcode .= "\n[b]DEFENSOR[/b]\n[b][size=19][color=blue]".trim($params["players"][1])."[/color][/size][/b]\n";

        if (!empty($params["tropas"]["defensor"])) {
            foreach ($params["tropas"]["defensor"] as $tropa => $cantidades) {
                $bbcode .= "$tropa [color=blue]".$cantidades[0]."[/color] [color=purple]Muertos: ".$cantidades[1]."[/color]\n";
            }
        } else {
            $bbcode .= "[b]Ninguna tropa[/b]\n";
        }
        $armas = $params["recursos"][1];
        $municion = $params["recursos"][2];
        $alcohol = $params["recursos"][3];
        $dolares = $params["recursos"][4];

        $winner = $this->getWinner($params["tropas"], $params["players"][0], $params["players"][1]);

        if ($winner !== false) {
            $bbcode .= "\n[b]".$winner." ha ganado la batalla![/b]";

            if (!empty($params["recursos"]) && $winner == $params["players"][0]) {
                $bbcode .= "\n".trim($params["recursos"][0][0]).": [b][color=blue]".trim($armas[1])."[/color][/b] ".trim($armas[0]).", ";
                $bbcode .= "[b][color=blue]".trim($municion[1])."[/color][/b] ".trim($municion[0]).", ";
                $bbcode .= "[b][color=blue]".trim($alcohol[1])."[/color][/b] ".trim($alcohol[0]).", ";
                $bbcode .= "[b][color=blue]".trim($dolares[1])."[/color][/b] ".trim($dolares[0]);
            }

        } else {
            $bbcode .= "\n[b]La batalla termina en empate![/b]";
        }


        /*[b]RESOURCES LOST IN THE BATLE[/b]
        Resources lost by the Striker: [b][size=18][color=red]1.535.091.000[/color][/size] units.[/b]
        Resources lost by the Defender: [b][size=18][color=blue]2.157.491.900[/color][/size] units.[/b]
        TOTAL of resources lost: [b][size=18][color=green]3.692.582.900[/color][/size] units.[/b]

        [b]POINTS LOST[/b]
        The Striker lost a total of [color=red][b]7.217.059[/b][/color] points.
        The Defender lost a total of [color=blue][b]10.173.890[/b][/color] points.
        Total of Points Lost: [b][size=18][color=green]17.390.949[/color][/size][/b] points.

        [b]PROFITABILITY[/b]
        Profitability of the Striker: [color=red][b]-100%[/b][/color]
        Recovery of the Striker by the resources stolen: [b][color=red]7.388.289[/color][/b] units.
        */

        $bbcode .= "\n\n[color=navy][b]Compactado con:[/b][/color][b] [url=http://stidda-tools.appspot.com/batallas][color=black]StiddariTools - Beta Version[/color][/url][color=navy] - ©Copyright by [/color][url=http://board.stiddari.com/profile.php?userid=2660][color=black]Luxifer[/color][/url][/b]";

        return $bbcode;
    }

}