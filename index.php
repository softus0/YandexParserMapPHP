<?php
// https://search-maps.yandex.ru/v1/?text=Приём%20и%20ску́пка%20металлолома%20Новосибирск&ll=83.147766,54.952020&spn=0.631020,0.231666&type=biz&lang=ru&results=500&apikey=edb36b6b-b7e7-4b79-bf98-f3aac0f99eec
// https://search-maps.yandex.ru/v1/?text=Приём%20и%20ску́пка%20металлолома%20Москва&type=biz&lang=ru&results=500&apikey=edb36b6b-b7e7-4b79-bf98-f3aac0f99eec
// https://yandex.ru/maps/?mode=search&text=55.749266%2C37.536953
// https://yandex.ru/maps/?mode=search&text={$latitude}%2C{$longitude}

require 'vendor/autoload.php'; // Подключите автозагрузчик Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

// Чтение JSON данных из файла org.json
$jsonData = file_get_contents('org.json');

// Декодирование JSON данных
$data = json_decode($jsonData, true);

$featuresList = [];

// Проход по элементам features
foreach ($data['features'] as $feature) {
    $coordinates = $feature['geometry']['coordinates'] ?? [];
    $longitude = $coordinates[0] ?? null;
    $latitude = $coordinates[1] ?? null;

    if ($longitude !== null && $latitude !== null) {
        $featuresList[] = [
            'feature' => $feature,
        ];
    }
}

// Создание нового объекта Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Организации');

// Заголовки таблицы
$headers = ['Айди', 'Название', 'Адрес', 'Телефон', 'Часы работы', 'Сайт', 'Координаты'];
$sheet->fromArray($headers, NULL, 'A1');

// Вывод данных в таблицу и в Excel
$rowNumber = 2;
$nnn = 1;
foreach ($featuresList as $item) {
    $feature = $item['feature'];
    $id = $nnn;
    $name = $feature['properties']['name'] ?? 'N/A';
    $address = $feature['properties']['CompanyMetaData']['address'] ?? 'N/A';
    $phone = isset($feature['properties']['CompanyMetaData']['Phones'][0]['formatted']) ? 
             $feature['properties']['CompanyMetaData']['Phones'][0]['formatted'] : 
             'N/A';
    $hours = $feature['properties']['CompanyMetaData']['Hours']['text'] ?? 'N/A';
    $url = $feature['properties']['CompanyMetaData']['url'] ?? '#';
    $coordinates = $feature['geometry']['coordinates'] ?? [];
    $longitude = $coordinates[0] ?? 'N/A';
    $latitude = $coordinates[1] ?? 'N/A';

    // Заполняем строки таблицы
    $sheet->fromArray([$id, $name, $address, $phone, $hours, $url, "https://yandex.ru/maps/?mode=search&text={$latitude}%2C{$longitude}"], NULL, "A{$rowNumber}");
    $rowNumber++;
    $nnn++;
}

// Сохранение в Excel файл
$writer = new Xlsx($spreadsheet);
$filename = 'organizations.xlsx';
$writer->save($filename);

// Начало HTML таблицы
ob_start(); // Начало буферизации вывода
echo '<link rel="stylesheet" href="styles.css">';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Название</th><th>Адрес</th><th>Телефон</th><th>Часы работы</th><th>Сайт</th><th>Координаты</th></tr>';

foreach ($featuresList as $item) {
    $feature = $item['feature'];
    $name = $feature['properties']['name'] ?? 'N/A';
    $address = $feature['properties']['CompanyMetaData']['address'] ?? 'N/A';
    $phone = isset($feature['properties']['CompanyMetaData']['Phones'][0]['formatted']) ? 
             $feature['properties']['CompanyMetaData']['Phones'][0]['formatted'] : 
             'N/A';
    $hours = $feature['properties']['CompanyMetaData']['Hours']['text'] ?? 'N/A';
    $url = $feature['properties']['CompanyMetaData']['url'] ?? '#';
    $coordinates = $feature['geometry']['coordinates'] ?? [];
    $longitude = $coordinates[0] ?? 'N/A';
    $latitude = $coordinates[1] ?? 'N/A';

    $mapUrl = "https://yandex.ru/maps/?mode=search&text={$latitude}%2C{$longitude}";

    // Вывод каждого элемента в новой строке
    echo '<tr>';
    echo "<td>{$name}</td>";
    echo "<td>{$address}</td>";
    echo "<td>{$phone}</td>";
    echo "<td>{$hours}</td>";
    echo "<td><a href='{$url}' target='_blank'>Сайт</a></td>";
    echo "<td><a href='{$mapUrl}' target='_blank'>Координаты ({$latitude}, {$longitude})</a></td>";
    echo '</tr>';
}

// Завершение HTML таблицы
echo '</table>';
$htmlContent = ob_get_clean(); // Сохранение буферизированного вывода в переменную

// Создание экземпляра Dompdf и генерация PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Установка шрифта, поддерживающего кириллицу

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper('A4', 'portrait'); // Установка формата бумаги
$dompdf->render();

// Сохранение PDF в файл
$output = $dompdf->output();
file_put_contents('organizations.pdf', $output); // Сохранение PDF в корень сайта
?>
