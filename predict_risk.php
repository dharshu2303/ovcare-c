<?php
header("Content-Type: application/json");
require 'db.php';

$patient_id = intval($_SERVER['HTTP_AUTHORIZATION']);
$sql = "SELECT CA125, HE4, heart_rate, temperature, sleep_hours, symptoms FROM biomarker_data WHERE patient_id=$patient_id ORDER BY recorded_at DESC LIMIT 1";
$res = $conn->query($sql);

if($res->num_rows == 1) {
  $data = $res->fetch_assoc();
  $payload = [
    "CA125" => floatval($data["CA125"]),
    "HE4" => floatval($data["HE4"]),
    "hr" => floatval($data["heart_rate"]),
    "temp" => floatval($data["temperature"]),
    "sleep" => floatval($data["sleep_hours"])
  ];
  
  $ch = curl_init('http://localhost:5000/predict');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
  $response = curl_exec($ch);
  $python_output = json_decode($response, true);
  curl_close($ch);

  // Dummy SHAP-style explanation (normally from Python)
  $explanation = [
    "CA125" => $payload["CA125"] >= 35 ? "High" : "Normal",
    "HE4" => $payload["HE4"] >= 140 ? "High" : "Normal",
    "Symptoms" => $data["symptoms"]
  ];

  echo json_encode([
    "risk" => $python_output["risk"],
    "explanation" => $explanation
  ]);
} else {
  echo json_encode(["risk"=>null, "explanation"=>null]);
}
$conn->close();
?>