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

if ($input["type"] == "StatementItem") {
  if (!file_exists("export")) {
    mkdir("export");
  }
  $fileName = "export/".date("Ym").".csv";

  // if (!file_exists($fileName)) {
  //   file_put_contents($fileName, "");
  //   $result["action"][] = "createFile";
  // }

  $file = fopen($fileName, "a");

  // Фільтр
  if ($input["data"]["account"] == "_kYRcaZNtbFAq4afS159Dw" && $input["data"]["statementItem"]["amount"] > 0) {
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
    if ($input["data"]["statementItem"]["counterIban"] == "UA213220010000026000330092169") {
      $string = [
        "3415815770",
        date("d.m.Y H:i:s", ($input["data"]["statementItem"]["time"])),
        round(($input["data"]["statementItem"]["operationAmount"] / 100), 2),
        $input["data"]["statementItem"]["description"]."\n".$input["data"]["statementItem"]["comment"],
        "Дохід",
        "Основний дохід",
        "MonoBank EUR",
        "EUR",
        "MonoBank",
        "UAH"
      ];
    } else if ($input["data"]["statementItem"]["amount"] > 0) {
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

  if ($string != NULL) {
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

echo json_encode($result);
$log["result"] = $result;
send_forward(json_encode($log), "https://log.mufiksoft.com/mono-export");