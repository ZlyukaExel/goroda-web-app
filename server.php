<?php
session_start();

/*/ Проверяем, существует ли флаг 'is_reloaded' в сессии
if (isset($_SESSION['is_reloaded'])) {
    // Очищаем сессию
    session_unset(); // Очищает все переменные сессии
    session_destroy(); // Уничтожает сессию
}

// Устанавливаем флаг, что страница была загружена
$_SESSION['is_reloaded'] = true;
*/
function loadCities($filename) {
    //чтение файла
    $cities = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $cityStatus = [];

    if (!isset($_SESSION['used_vector'])) {
        //если сессия пустая, все used = false
        foreach ($cities as $city) {
            $cityStatus[] = ['name' => trim($city), 'used' => false];
        }
    } else {
        //считываем вектор из сессии
        $used_vector = $_SESSION['used_vector'];
        foreach ($cities as $index => $city) {
            $cityStatus[] = [
                'name' => trim($city), 
                'used' => isset($used_vector[$index]) ? (bool)$used_vector[$index] : false
            ];
        }
    }

    return $cityStatus;
}

function saveCities($cityArray) {
    $used_vector = [];
    foreach ($cityArray as $city) {
        $used_vector[] = $city['used'] ? 1 : 0; //преобразуем массив в вектор
    }
    $_SESSION['used_vector'] = $used_vector;
}

function isCityUsed($city, $cityStatus) {
    foreach ($cityStatus as $entry) {
        if (mb_strtolower($entry['name'], 'UTF-8') === mb_strtolower($city, 'UTF-8')) {
            return $entry['used'];
        }
    }
    return false;
}

function isCityExist($city, $cityStatus) {
    foreach ($cityStatus as $entry) {
        if (mb_strtolower($entry['name'], 'UTF-8') === $city) {
            return true;
        }
    }
    return false;
}

//получение последней буквы, игнорируя ь, ы, ъ
function lastChar($city) {
	$lastLetter = mb_substr($city, -1);
	if ($lastLetter === "ь" || $lastLetter === "ы" || $lastLetter === "ъ"){
		$lastLetter = mb_substr($city, -2, 1);
	}
	return $lastLetter;
}

//основной код приложения ================================================================
$current_text = 'Напиши название первого города<br>
				и программа тебе ответит:
				<form method="POST">
					<input type="text" name="inputField">
					<button type="submit" name="ok">Подтвердить!</button>
				</form>'; 

$cityStatus = []; 

if (isset($_POST['ok'])) {
	$citiesFile = 'cities.txt'; 
	$userCity = htmlspecialchars(mb_strtolower($_POST['inputField'], 'UTF-8'));
	//создание массива
	$cities = file($citiesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	

	if (!isset($_SESSION['used_vector'])) {
		//если сессия пустая, то used = false
		foreach ($cities as $city) {
			$cityStatus[] = ['name' => trim($city), 'used' => false];
		}
		$current_text = 'Напиши название первого города<br>
						и программа тебе ответит:</p>
						<form method="POST">
							<input type="text" name="inputField">
							<button type="submit" name="ok">Подтвердить!</button>
						</form>';
	} else {
		//считываем вектор из сессии
		$used_vector = $_SESSION['used_vector'];
		foreach ($cities as $index => $city) {
			$cityStatus[] = [
				'name' => trim($city), 
				'used' => isset($used_vector[$index]) ? (bool)$used_vector[$index] : false
			];
		}
		
		//необходимая буква
		$required_letter = "";
		if(isset($_SESSION['last_letter'])){
			$required_letter = $_SESSION['last_letter'];
		}
		
		//буква подходит?
		if (!(($required_letter === "") || ($required_letter === mb_substr($userCity, 0, 1)))) {
				$current_text = "Этот город не подходит. Попробуйте другой!<br>
								Придумай город на букву \"{$required_letter}\"<br>
								<form method=\"POST\">
									<input type=\"text\" name=\"inputField\">
									<button type=\"submit\" name=\"ok\">Подтвердить!</button>
								</form>";
		} else {
				//такой город существует?
				if (!isCityExist($userCity, $cityStatus)) {
					if(!($required_letter === "")){
						$current_text = "Такого города не существует. Попробуйте другой!<br>
										Придумай город на букву \"{$required_letter}\"<br>
										<form method=\"POST\">
											<input type=\"text\" name=\"inputField\">
											<button type=\"submit\" name=\"ok\">Подтвердить!</button>
										</form>";
					}else{
						$current_text = "Такого города не существует! Попробуйте другой!<br>
										<form method=\"POST\">
											<input type=\"text\" name=\"inputField\">
											<button type=\"submit\" name=\"ok\">Подтвердить!</button>
										</form>
										<br>";
					}
				} else {
					//город уже был назван?
					if (isCityUsed($userCity, $cityStatus)) {
						$current_text = "Этот город уже был назван. Попробуйте другой!<br>
										Придумай город на букву \"{$required_letter}\"<br>
										<form method=\"POST\">
											<input type=\"text\" name=\"inputField\">
											<button type=\"submit\" name=\"ok\">Подтвердить!</button>
										</form>";
					} else {							
						//отметить город как использованный
						foreach ($cityStatus as &$entry) {
							if (mb_strtolower($entry['name'], 'UTF-8') === $userCity) {
								$entry['used'] = true;
							}
						}

						//последняя буква
						$lastChar = lastChar($userCity);
				
						//поиск ответа
						$foundCity = false;
						foreach ($cityStatus as $key => $entry) {
							if (!$entry['used'] && mb_substr(mb_strtolower($entry['name'], 'UTF-8'), 0, 1) === $lastChar){
								$lastLet = lastChar($entry['name']);
								$current_text = "{$entry['name']}!<br>
												Придумай город на \"{$lastLet}\":</p>
												<form method=\"POST\">
													<input type=\"text\" name=\"inputField\">
													<button type=\"submit\" name=\"ok\">Подтвердить!</button>
												</form>";
								$cityStatus[$key]['used'] = true;
								$foundCity = true;
								
								$_SESSION['last_letter'] = $lastLet;
								break;
							}
						}
					
						if (!$foundCity) {
							session_unset(); //очищает все переменные сессии
							session_destroy(); //уничтожает сессию
							$current_text = "Программа не нашла города, начинающегося с '$lastChar' <br> 
											Вы выиграли! Поздравляем!</p>
											<form action=\"server.php\">
												<button type=\"submit\" style=\"width:200px; height: 50px; font-size:24px;\">Давай ещё раз!</button>
											</form>";
							}
						}
			}
		}
	}
}

//для упрощения игры можно показывать использованные города, сильно заморачиваться с этим не стал
foreach ($cityStatus as $entry) {
	if ($entry['used']) {
		echo "<br>{$entry['name']} is used";
	}
}

//сохранение массива 
saveCities($cityStatus);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Игра в города!</title>
	<style>
        main {
            text-align: center;
            position: absolute;
			top: 185px;
			line-height: 2;
            width: 100%;
        }
    </style>
</head>

<body>
<div class="class">
	<main>
		<h1> Игра в города </h1>
		<h3>
			<p><?php echo $current_text; ?>
		</h3>
		<br>
	
		<br><br><br><br><br><br><a href="https://t.me/KoleraOficiala">Связаться с автором</a>
	</main>
</div>
</body>

</html>