<?php


function displayDropdownMenu() {
    echo "<h1>Iskolanévsor</h1>";
    echo "<form method='POST' id='classForm'>";
    echo "<span id='classSelector'>Választó: </span>";
    echo "<select id='classOption' name='view' onchange='document.getElementById(\"classForm\").submit();'>";
    echo "<option value='all'>Teljes iskolanévsor</option>";
    foreach (CLASSES as $class) {
        echo "<option value='$class'>$class osztály</option>";
    }
    echo "<option value='averages'>Átlagok</option>";
    echo "</select>";
    echo '<button type="submit" name="newSchool" value="1">Új iskola</button>';
    try {
        if(isset($_SESSION['view']) && $_SESSION['view'] == "averages") {
            echo "<br>";
            echo '<button type="submit" name="fDownload" value="1">Osztály Szinten Letöltés</button>';
            echo '<button type="submit" name="sDownload" value="1">Tantárgy Szinten Letöltés</button>';
            echo '<button type="submit" name="tDownload" value="1">Tantárgy Átlag Letöltés</button>';
        } else {
            echo '<button type="submit" name="export_csv" value="1">Letöltés</button>';
            echo '<button type="submit" name="exportsql" value="1">SQL export</button>';
        }
    } catch (Exception $e) {
        echo '<button type="submit" name="export_csv" value="1">Letöltés</button>';
        echo '<button type="submit" name="exportsql" value="1">SQL export</button>';
    }
    echo "</form>";
    echo '<div id="hr"></div>';
}

function displayClass($classList, $class = null) {
    if ($class == 'all') {
        foreach ($classList as $className => $students) {
            echo "<h3>$className osztály - ".calcClassAverage($className)."</h3>";
            displayStudents($students);
        }
    } elseif (isset($classList[$class])) {
        echo "<h3>$class osztály - ".calcClassAverage($class)."</h3>";
        displayStudents($classList[$class]);
    } else {
        echo "<p>Nincs ilyen osztály.</p>";
    }
}

function displayStudents($students) {
    echo '<div class="students">';
    
    foreach ($students as $student) {
        echo '<div class="student">';
        echo "<center><strong>{$student['name']}</strong> - {$student['class']} - {$student['gender']} - <strong>{$student['average']}</strong></center>";
        echo "<table>";
        
        foreach ($student['grades'] as $subject => $grades) {
            echo "<tr>";
            echo "<td>";
            echo ucfirst($subject).": " . implode(', ', $grades);
            echo "</td>";
            echo "<td class='right'>";
            echo "<strong>";
            $average = 0;
            foreach($grades as $grade) {
                $average += $grade;
            }
            if(count($grades) == 0) {
                $average = "-";
            } else {
                $average = $average / count($grades);
            }
            
            echo number_format((float)$average, 2, '.', '');
            echo "</strong>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo '</div>';
    }
    
    echo '</div>';
}

function displayAvaragesHeader() {
    echo "<table id='asd'>";
    
    echo "<tr>";
    echo "<td>";
    echo "<h3>Osztály szinten</h3>";
    echo "</td>";
    echo "<td>";
    echo "<h3>Tantárgyonként</h3>";
    echo "</td>";
    echo "<td>";
    echo "<h3>Tantárgy átlag</h3>";
    echo "</td>";
    echo "</tr>";
}
function getAvaragesByClass($averages){
    $index = 1;
    $array = [];
    foreach($averages as $name => $average){
        $array[] = [$index,$name,$average];
        $index++;
    }
    return $array;
}
function displayAvaragesByClass($averages) {
    echo "<tr>";
    echo "<td>";
    
    echo "<div class='table leftes'>";
    $array = getAvaragesByClass($averages);
    foreach($array as $data){
        echo "<div class='row va'>";
        echo "<div class='cell'>";
        echo "<span class='index'>".$data[0]."</span>";
        echo "</div>";
        echo "<div class='cell'>";
        echo "<span class='text'>".$data[1]."</span>";
        echo "</div>";
        echo "<div class='cell'>";
        echo "<strong>".$data[2]."</strong>";   
        echo "</div>";  
        echo "</div>";
    }
    echo "</div>";
    echo "</td>";
}

function calcSubjectAvarages($class){
    $subjectAverages = [];
    foreach(SUBJECTS as $subject) {
        foreach ($_SESSION["classList"][$class] as $student) {
            foreach ($student['grades'] as $sj => $grades) {
                if($subject == $sj) {
                    $averageSj = 0;
                    $biggestAverageSj = 0;
                    $biggestName = "";
                    foreach($grades as $grade) {
                        $averageSj += $grade;
                    }
                    if($averageSj != 0) {
                        $averageSj /= count($grades);
                        if($averageSj > $biggestAverageSj) {
                            $subjectAverages[$subject][$student["name"]] = $averageSj;
                            break;
                        }
                        
                    }
                }
            }
        }
    }
    foreach ($subjectAverages as $key => &$subArray) {
        $maxValue = max($subArray);
        $subArray = array_filter($subArray, function($value) use ($maxValue) {
            return $value == $maxValue;
        });
    }
    unset($subArray);
    return $subjectAverages;
}
function getAvaragesBySubjects($class) {
    $subjectAverages = calcSubjectAvarages($class);
    $array = [];
    foreach($subjectAverages as $subject => $sa) {
        
        $av = 0;
        $array2 = [];
        foreach($sa as $student => $average) {
            
            $av = number_format((float)$average, 2, '.', '');
            $array2[] = $student;
        }
        $array[$subject] = [$array2,$av];
    }
    return $array;

} 
function displayAvaragesBySubjects($class) {
    echo "<td>";    
    echo "<div class='table leftes'>";
    $array = getAvaragesBySubjects($class);
    foreach($array as $subject => $data) {
        echo "<div class='row'>";
        echo "<div class='cell'><b>".ucfirst($subject)."</b></div>";
        echo "<div class='cell'>";
        foreach($data[0] as $student) {
            
            echo $student."<br>";
            
            
        }
        echo "</div>";
        echo "<div class='cell'><b>".$data[1]."</b></div>";
        echo "</div>";
        
    }
    echo "</div>";
    echo "</td>";
    echo "<td>";
    echo "<div class='table'>";
}
function getSubjectAvarages($class) {
    $array = [];
    foreach (SUBJECTS as $subject) {
        $avr = 0;
        $avrDiv = 0;
        foreach ($_SESSION["classList"][$class] as $student) {
            foreach ($student['grades'] as $sj => $grades) {
                if($sj == $subject) {
                    foreach($grades as $grade) {
                        $avr += $grade;
                        $avrDiv += 1;
                    }
                }
            }
        }
        $array[$subject] = number_format((float)($avr/$avrDiv), 2, '.', '');
        
    }
    return $array;
}
function displaySubjectAvarages($class) {
    $array = getSubjectAvarages($class);
    foreach ($array as $subject => $av) {
        echo "<div class='row'>";
        echo "<div class='cell'>";
        echo "<b>".ucfirst($subject)."</b>";
        echo "</div>";
        echo "<div class='cell' style='text-align: right; margin-right: 10px'>";

        echo "<b>".$av."</b>";
        echo "</div>";
        echo "</div>";
        
    }
    echo "</div>";
    echo "</td>";
    echo "</table>";
    
}

function displayAveragesByClass($class) {
    $averages = [];

    foreach ($_SESSION["classList"][$class] as $student) {
        $averages[$student["name"]] = $student["average"];
    } 
    arsort($averages);
    
    echo "<h2>".$class." osztály</h2>";
    displayAvaragesHeader();

    
    displayAvaragesByClass($averages);
    
    
    displayAvaragesBySubjects($class);
    
    displaySubjectAvarages($class);
    
    
}

function displayClassAverageSelector(){
    echo "<form method='POST' id='classAverageForm'>";
    foreach(CLASSES as $class) {
    
        echo '<button type="submit" name="'.$class.'" value="1">'.$class.'</button>';
    }
    echo "</form>";
}

?>


