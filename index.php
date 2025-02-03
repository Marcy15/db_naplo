<?php
session_start();
require 'config.php';
require 'classroom-render.php';
require 'mysql.php';
if (isset($_POST['view'])) {
    $_SESSION['view'] = $_POST['view'];
}

if (!isset($_SESSION['classList'])) {
    genClassListSQL();
}

function genClassListSQL() {
    $_SESSION['classList'] = generateClassList();
    //loadToDataBase();
}

function loadToDataBase(){
    if(!dbExists()) {
        createDatabase();
        execSql("CREATE TABLE osztaly (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(10) NOT NULL
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci");
        execSql("CREATE TABLE nev (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            class_id INT NOT NULL,
            FOREIGN KEY (class_id) REFERENCES osztaly(id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci");
        execSql("CREATE TABLE tantargyak (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci");
        execSql("CREATE TABLE osztalyzat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            grade TINYINT NOT NULL,
            FOREIGN KEY (student_id) REFERENCES nev(id),
            FOREIGN KEY (subject_id) REFERENCES tantargyak(id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci");

        /*foreach(CLASSES as $class) {
            execSql("INSERT INTO osztaly (name) VALUES ('$class')");
        }
        foreach(SUBJECTS as $subject) {
            execSql("INSERT INTO tantargyak (name) VALUES ('$subject')");
        }*/
        $classIds = [];
        $subjectIds = [];
        foreach ($_SESSION['classList'] as $class => $students) {
            // Insert osztályok egy batchben (ha nem létezik)
            $classIds[$class] = execSql("INSERT INTO osztaly (name) VALUES ('$class')");
            
            $studentData = [];
            $gradeData = [];
            $subjectData = [];

            foreach ($students as $student) {
                // Insert diák adatokat
                $studentId =execSql("INSERT INTO nev (name, gender, class_id) VALUES ('".$student["name"]."', '".$student["gender"]."', '".$classIds[$class]."')");
                
                // Hozzuk el az utolsó beszúrt rekord ID-ját
                echo $studentId;

                // Inserting grades and collecting subject IDs
                foreach ($student["grades"] as $subject => $grades) {
                    // Insert subject if not exists and get its ID
                    if (!isset($subjectIds[$subject])) {
                        $subjectIds[$subject] = execSql("INSERT IGNORE INTO tantargyak (name) VALUES ('$subject')");
                    }

                    // Ensure $grades is an array and process each grade
                    if (is_array($grades)) {
                        // Process each grade in the array
                        foreach ($grades as $grade) {
                            $gradeData[] = "('$studentId', '".$subjectIds[$subject]."', '$grade')";
                        }
                    } else {
                        // If $grades is a single value, treat it as one grade
                        $gradeData[] = "('$studentId', '".$subjectIds[$subject]."', '$grades')";
                    }
                }
            }

        // Inserting grades for students

        if (!empty($gradeData)) {
            execSql("INSERT INTO osztalyzat (student_id, subject_id, grade) VALUES " . implode(", ", $gradeData));
        }
    }
        
    } else {
        execSql("DROP DATABASE school");
    }
}


$currentAvarageView = CLASSES[0];
if (isset($_POST['export_csv'])) {
    loadToDataBase();
    /*$class = $_POST['view'];
    $timestamp = date('Y-m-d_Hi');
    ob_clean();


    $filename = "{$class}-{$timestamp}.csv";
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $file = fopen('php://output', 'w');
    $header = ['Osztaly', 'Nev', 'Nem', 'Tantargy', 'Jegyek'];
    fputcsv($file, $header);

    if($class == "all") {
        foreach(CLASSES as $c) {
            $students = $_SESSION['classList'][$c];
            foreach ($students as $student) {
                foreach ($student['grades'] as $subject => $grades) {
                    fputcsv($file, [
                        $student['class'],
                        $student['name'],
                        $student['gender'],
                        $subject,
                        implode(', ', $grades),
                    ]);
                }
            }
        }
    } else {
        $students = $_SESSION['classList'][$class];
        foreach ($students as $student) {
            foreach ($student['grades'] as $subject => $grades) {
                fputcsv($file, [
                    $student['class'],
                    $student['name'],
                    $student['gender'],
                    $subject,
                    implode(', ', $grades),
                ]);
            }
        }
    }

    fclose($file);
    exit;*/
}
function generateCsv($filename, $data,$vanKey = false) {
    ob_clean();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    foreach ($data as $key => $row) {
        if(is_string(($row))) {
            $row = [$row];
        } else {
            foreach ($row as &$item) {
                if (is_array($item)) {
                    $item = implode(", ", array: $item);
                }
            }
        }
        
        if($vanKey) {
            array_unshift($row, $key);
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
if (isset($_POST['newSchool'])) {


    genClassListSQL();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['fDownload'])) {
    $averages = [];
    $timestamp = date('Y-m-d_Hi');
    foreach ($_SESSION["classList"][$currentAvarageView] as $student) {
        $averages[$student["name"]] = $student["average"];
    } 
    arsort($averages);
    generateCsv("byClass-{$timestamp}.csv",getAvaragesByClass($averages));
}

if (isset($_POST['sDownload'])) {
    $timestamp = date('Y-m-d_Hi');
    generateCsv("bySubject-{$timestamp}.csv",getAvaragesBySubjects($currentAvarageView),true);
}
if (isset($_POST['tDownload'])) {
    $timestamp = date('Y-m-d_Hi');
    generateCsv("subjectAvarages-{$timestamp}.csv",getSubjectAvarages($currentAvarageView),true);
}

echo '<link rel="stylesheet" href="style.css">';
echo '<div id="main">';


function generateStudent($class) {
    $lastname = NAMES['lastnames'][array_rand(NAMES['lastnames'])];
    $isMale = (bool) rand(0, 1);
    $firstname = $isMale ? NAMES['firstnames']['men'][array_rand(NAMES['firstnames']['men'])] : NAMES['firstnames']['women'][array_rand(NAMES['firstnames']['women'])];
    
    $grades = [];
    $average = 0;
    $needToDivideBy = 0;
    foreach (SUBJECTS as $subject) {
        $gradeCount = rand(3, MARKS_COUNT);
        $grades[$subject] = array_map(fn() => rand(1, 5), range(1, $gradeCount));
        foreach($grades[$subject] as $grade) {
            $average += $grade;
            $needToDivideBy += 1;
        }
    }
    $average /= $needToDivideBy;
    
    $gender = "Fiú";
    if($isMale == 0) {
        $gender = "Lány";
    }
    $student = array();
    $student['name'] = $lastname . ' ' . $firstname;
    $student['class'] = $class;
    $student['grades'] = $grades;
    $student['gender'] = $gender;
    $student["average"] = floatval(number_format((float)$average, 2, '.', ''));


    return $student;
}

function calcClassAverage($class){
    $average = 0;
    foreach($_SESSION['classList'][$class] as $student) {
        $average += $student["average"];
    }
    $average /= count($_SESSION['classList'][$class]);
    return number_format((float)$average, 2, '.', '');;
}

function generateClassList() {
    $classList = [];
    foreach (CLASSES as $class) {
        $studentCount = rand(MIN_CLASS_COUNT, MAX_CLASS_COUNT);
        for ($i = 0; $i < $studentCount; $i++) {
            $classList[$class][] = generateStudent($class);
        }
    }
    return $classList;
}

$currentOption = 0;
displayDropdownMenu();
$averageMenu = false;
foreach(CLASSES as $class) {
    if(isset($_POST[$class])) {
        echo "<script>
            document.getElementById('classOption').selectedIndex=".(count(CLASSES)+1)."
            </script>
            ";
        $averageMenu = true;
        displayClassAverageSelector();
        displayAveragesByClass($class);
        $currentAvarageView = $class;
    }
}



if (!isset($_POST['view']) && !$averageMenu) {
    displayClass($_SESSION['classList'], "all");
}

function loadAfterRefresh(){
    $view = $_POST['view'];
    $index = 1;
    $currentOption = 0;
    if($view == "averages") {
        $currentOption = count(CLASSES)+1;
        displayClassAverageSelector();
        displayAveragesByClass(CLASSES[0]);

    } else {
        foreach (CLASSES as $class) {
            if($class == $view) {
                $currentOption = $index;
            }
            $index += 1;
        }
        displayClass($_SESSION['classList'], $view);
    }
    
    echo "<script>
    document.getElementById('classOption').selectedIndex=".$currentOption."
    </script>
    ";
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['view'])) {
    loadAfterRefresh();
}



echo '</div>';
?>


