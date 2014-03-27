<?php

class CombatParser {

    protected $locales;
    protected $originalLocale;
    protected $translations;

    public function getLocales() {
        if ($this->locales === null) {
            $this->locales = include "locales.php";
        }

        return $this->locales;
    }

    public function setOriginalLocale($originalLocale) {
        $this->originalLocale = $originalLocale;
    }

    public function setCurrentLocale($currentLocale) {
        $this->translations = array_combine(
                                $this->getLocales()[$this->originalLocale],
                                $this->getLocales()[$currentLocale]
                            );
    }

    public function t($string, $lang = null) {
        return $lang === null ? $this->translations[$string] : $this->getLocales()[$lang][$string];
    }

    function detectLanguage($tropas) {

        foreach ($this->getLocales()as $locale => $strings) {
            $found = true;
            foreach ($tropas as $tropa) {
                if (!in_array($tropa, $strings)) {
                    $found = false;
                    break;
                }
            }
            if ($found) return $locale;
        }

    }

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
            // si empieza con xx:xx:xx Informe de Batalla ... esta linea hay que eliminarla
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

        preg_match_all('/\d{1,3}:\d{1,3}:\d{1,3}/', $data[0], $coordenadas);
        if($coordenadas) $coordenadas = $coordenadas[0];

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
            $recursos = @reset(array_pop($parsedData));
        }
        $rondas = sizeof($parsedData);

        $players = array_map(function($i){
            return @end(explode("(", $i));
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
            "intro" => explode(":", $data[0], 2)[0],
            "date" => trim($data[1]),
            "coordenadas" => $coordenadas,
            "rondas" => $rondas,
            "players" => $players,
            "tropas" => $tropas,
            "recursos" => $recursos
        );

        return $params;
    }

    public function getTropa($spanishName) {
        $classNames = array(
            "Occupation Troop" => "OccupationTroop",
            "CIA Agent" => "CiaAgent",
            "FBI Agent" => "FbiAgent",
            "Tactical Expert" => "TacticalExpert",
            "Demolition Expert" => "DemolitionExpert",
            "Clandestine Worker" => "ClandestineWorker",
        );

        $englishName = $this->getLocales()["en"][$spanishName];

        $className = isset($classNames[$englishName]) ? $classNames[$englishName] : $englishName;
        $className = "\\VdtPlus\\Troop\\".$className;
        return new $className;
    }

    public function getBbCode($reporte) {
        $params = $this->parse($reporte);

        $language = $this->detectLanguage(array_keys($params["tropas"]["atacante"]));

        $this->setOriginalLocale($language);

        $bbcodes = array();

        foreach(array_keys($this->getLocales()) as $lang) {

            $this->setCurrentLocale($lang);

            $bbcode = "[b]".$this->t($params["intro"]).": ".$params["date"]."[/b]\n";
            //"La batalla duró [b][size=18]".$params["rondas"]."[/size][/b] ronda(s).\n";

            $pointsLost = array(0 => 0, 1 => 0);

            $bbcode .= "\n[b][size=19][color=red]".trim($params["players"][0])."[/color][/size][/b]\n";
            foreach ($params["tropas"]["atacante"] as $tropa => $cantidades) {
                $bbcode .= $this->t($tropa)." [color=red]".$cantidades[0]."[/color] [color=purple]".$this->t("Destruido", $lang).": ".$cantidades[1]."[/color]\n";
                $pointsLost[0] += $this->getTropa($tropa)->getPoints()*$cantidades[1];
            }

            $bbcode .= "\n[b][size=19]vs.[/size][/b]\n";

            /*$bbcode .= "[color=green]([i]Losses: 494.208.000 Weapons, 732.779.000 Ammunition, 308.104.000 Dollar[/i])[/color]
            [b]Chances of Winning: [color=green]80% 20%[/color][/b]";*/

            $bbcode .= "\n[b][size=19][color=blue]".trim($params["players"][1])."[/color][/size][/b]\n";

            if (!empty($params["tropas"]["defensor"])) {
                foreach ($params["tropas"]["defensor"] as $tropa => $cantidades) {
                    $bbcode .= $this->t($tropa)." [color=blue]".$cantidades[0]."[/color] [color=purple]".$this->t("Destruido", $lang).": ".$cantidades[1]."[/color]\n";
                    $pointsLost[1] += $this->getTropa($tropa)->getPoints()*$cantidades[1];
                }
            } else {
                $bbcode .= "[b]-[/b]\n";
            }
            $armas = $params["recursos"][1];
            $municion = $params["recursos"][2];
            $alcohol = $params["recursos"][3];
            $dolares = $params["recursos"][4];

            $winner = $this->getWinner($params["tropas"], $params["players"][0], $params["players"][1]);

            if ($winner !== false) {
                // $bbcode .= "\n[b]".$winner." ".$this->t("ha ganado la batalla!")."[/b]";

                if (!empty($params["recursos"]) && $winner == $params["players"][0]) {
                    $bbcode .= "\n".$this->t(trim($params["recursos"][0][0])).": [b][color=blue]".trim($armas[1])."[/color][/b] ".$this->t(trim($armas[0])).", ";
                    $bbcode .= "[b][color=blue]".trim($municion[1])."[/color][/b] ".$this->t(trim($municion[0])).", ";
                    $bbcode .= "[b][color=blue]".trim($alcohol[1])."[/color][/b] ".$this->t(trim($alcohol[0])).", ";
                    $bbcode .= "[b][color=blue]".trim($dolares[1])."[/color][/b] ".$this->t(trim($dolares[0]));
                }

            } else {
                // $bbcode .= "\n[b]".$this->t("La batalla termina en empate!")."[/b]";
            }


            /*[b]RESOURCES LOST IN THE BATLE[/b]
            Resources lost by the Striker: [b][size=18][color=red]1.535.091.000[/color][/size] units.[/b]
            Resources lost by the Defender: [b][size=18][color=blue]2.157.491.900[/color][/size] units.[/b]
            TOTAL of resources lost: [b][size=18][color=green]3.692.582.900[/color][/size] units.[/b]
            */
            $bbcode .= "\n\n[b]POINTS LOST[/b]\n";
            $bbcode .= trim($params["players"][0])." lost a total of [color=red][b]".number_format($pointsLost[0], 0)."[/b][/color] points.\n";
            $bbcode .= trim($params["players"][1])." lost a total of [color=blue][b]".number_format($pointsLost[1], 0)."[/b][/color] points.\n";

            /*
            [b]PROFITABILITY[/b]
            Profitability of the Striker: [color=red][b]-100%[/b][/color]
            Recovery of the Striker by the resources stolen: [b][color=red]7.388.289[/color][/b] units.
            */

            $bbcode .= "\n\n[b][url=http://stidda-tools.appspot.com/batallas][color=black]StiddariTools - Beta Version[/color][/url][color=navy] - ©Copyright by [/color][url=http://board.stiddari.com/profile.php?userid=2660][color=black]Luxifer[/color][/url][/b]";

            $bbcodes[$lang] = $bbcode;
        }
        return $bbcodes;
    }

}