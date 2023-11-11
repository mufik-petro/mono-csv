<?php
header("Content-type: application/json; charset=utf-8");

function send_forward($inputJSON, $link){
  $request = "POST";
  $descriptor = curl_init($link);
  curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
  curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($descriptor, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
  curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);
  $itog = curl_exec($descriptor);
  curl_close($descriptor);
  return $itog;
}

$result["state"] = true;
$log["result"] = $result;

$input = json_decode(file_get_contents("php://input"), true);
$log["input"] = $input;

// фільтр вебхуку "Лише події транзакцій"
if ($input["type"] == "StatementItem") { 
  if (!file_exists("export")) {
    // Створення папки для csv файлів
    mkdir("export"); 
  }
  // Назва файлу для збереження (окремий файл на кожен місяць)
  $fileName = "export/".date("Ym").".csv"; 
  // Відкриття файлу для додавання даних (створюється, якщо відсутній)
  $file = fopen($fileName, "a"); 

  // Фільтр
  if ($input["data"]["account"] == "_kYRcaZNtbFAq4afS159Dw" && $input["data"]["statementItem"]["amount"] > 0) {
    // Євровий рахунок ФОП тільки вхідні транзакції
    $string = [
      "3415815770",
      date("d.m.Y H:i:s", $input["data"]["statementItem"]["time"]),
      round(($input["data"]["statementItem"]["amount"] / 100), 2),
      $input["data"]["statementItem"]["description"]."\n".$input["data"]["statementItem"]["comment"],
      "Дохід",
      "Основний дохід",
      "MonoBank EUR",
      "EUR"
    ];
  } else if ($input["data"]["account"] == "wUW799mkoxQhqrlZPLtC6w") {
    // Гривневий рахунок ФОП
    if ($input["data"]["statementItem"]["counterIban"] == "UA213220010000026000330092169") {
      // Транзакція переказу з Єврового рахунку ФОП
      $string = [
        "3415815770",
        date("d.m.Y H:i:s", ($input["data"]["statementItem"]["time"])),
        round(($input["data"]["statementItem"]["operationAmount"] / 100), 2),
        $input["data"]["statementItem"]["description"]."\n".$input["data"]["statementItem"]["comment"],
        "Обмін валюти",
        "Основний дохід",
        "MonoBank EUR",
        "EUR",
        "MonoBank",
        "UAH"
      ];
    } else if ($input["data"]["statementItem"]["amount"] > 0) {
      // Вхідна транзакція
      $string = [
        "3415815770",
        date("d.m.Y H:i:s", ($input["data"]["statementItem"]["time"])),
        round(($input["data"]["statementItem"]["amount"] / 100), 2),
        $input["data"]["statementItem"]["description"]."\n".$input["data"]["statementItem"]["comment"],
        "Дохід",
        "Основний дохід",
        "MonoBank",
        "UAH"
      ];
    } else {
      // Вихідна транзакція
      $string = [
        "3415815770",
        date("d.m.Y H:i:s", ($input["data"]["statementItem"]["time"])),
        round((abs($input["data"]["statementItem"]["amount"]) / 100), 2),
        $input["data"]["statementItem"]["description"]."\n".$input["data"]["statementItem"]["comment"],
        "Витрата",
        "Основна витрата",
        "MonoBank",
        "UAH"
      ];
    }
  }
  $log["insert"] = $string;

  // Перевірка наявності даних для внесення в файл
  if ($string != NULL) {
    // Внесення з перевіркою на успіх
    if (fputcsv($file, $string) === false) {
      $result["state"] = false;
      $result["error"]["message"][] = "failed create export file";
    }
  } else {
    $result["state"] = false;
    $result["error"]["message"][] = "not found data to insert";
  }
} else {
  $result["state"] = false;
  $result["error"]["message"][] = "not supported type";
}

// Відповідь
echo json_encode($result);
$log["result"] = $result;
// Відправка логу для збереження (власний приватний аналог webhook.site)
send_forward(json_encode($log), "https://log.mufiksoft.com/mono-export");
