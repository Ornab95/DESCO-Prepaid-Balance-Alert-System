<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$DESCO_ID = '31394051';
$USER_EMAIL = 'sdfsdfsdfsdfs@gmail.com';
$SMTP_FROM_EMAIL = 'arnabbiswas426@gmail.com';
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_USER = 'arnabbiswas426@gmail.com';
$SMTP_PASSWORD = 'hpgo mpqp dbez hbss';

// Function to send email using fsockopen and Gmail SMTP
function sendEmail($to, $subject, $body, $from, $smtpHost, $smtpPort, $smtpUser, $smtpPassword) {
    $log = '';

    $smtpConnect = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "Connection Response: $smtpResponse\n";
    if (empty($smtpConnect)) {
        file_put_contents('email_log.txt', $log);
        return "Failed to connect: $smtpResponse";
    }

    fputs($smtpConnect, "HELO $smtpHost\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "HELO Response: $smtpResponse\n";

    fputs($smtpConnect, "STARTTLS\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "STARTTLS Response: $smtpResponse\n";

    stream_socket_enable_crypto($smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    fputs($smtpConnect, "HELO $smtpHost\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "HELO (after STARTTLS) Response: $smtpResponse\n";

    fputs($smtpConnect, "AUTH LOGIN\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "AUTH LOGIN Response: $smtpResponse\n";

    fputs($smtpConnect, base64_encode($smtpUser) . "\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "Username Response: $smtpResponse\n";

    fputs($smtpConnect, base64_encode($smtpPassword) . "\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "Password Response: $smtpResponse\n";

    fputs($smtpConnect, "MAIL FROM: <$from>\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "MAIL FROM Response: $smtpResponse\n";

    fputs($smtpConnect, "RCPT TO: <$to>\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "RCPT TO Response: $smtpResponse\n";

    fputs($smtpConnect, "DATA\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "DATA Response: $smtpResponse\n";

    fputs($smtpConnect, "Subject: $subject\r\n");
    fputs($smtpConnect, "To: <$to>\r\n");
    fputs($smtpConnect, "From: <$from>\r\n");
    fputs($smtpConnect, "\r\n$body\r\n.\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "Message Body Response: $smtpResponse\n";

    fputs($smtpConnect, "QUIT\r\n");
    $smtpResponse = fgets($smtpConnect, 515);
    $log .= "QUIT Response: $smtpResponse\n";

    fclose($smtpConnect);

    // Save the log for debugging purposes
    file_put_contents('email_log.txt', $log);
    debug_to_console($smtpConnect);
    debug_to_console(strpos($smtpResponse, '550'));
    debug_to_console(strpos($smtpResponse, '250'));
    debug_to_console(strpos($smtpResponse, '450'));

    if(empty(strpos($smtpResponse, '250'))){
        return true;
    }
    else{
        return false;
    }
   // return strpos($smtpResponse, '550') !== true;
}


function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}
// Function to fetch data using cURL
function fetchData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        echo $error_msg;
    }
    curl_close($ch);
    return $response;
}

// Get balance data
$balanceApiUrl = "https://prepaid.desco.org.bd/api/tkdes/customer/getBalance?accountNo=$DESCO_ID";
$balanceResponse = fetchData($balanceApiUrl);
//ver_dum($balanceResponse);
$balanceData = json_decode($balanceResponse, true);
$balance = $balanceData['data']['balance'];

// Get daily consumption data
$dateFrom = date('Y-m-d', strtotime('-7 days'));
$dateTo = date('Y-m-d');
$consumptionApiUrl = "https://prepaid.desco.org.bd/api/tkdes/customer/getCustomerDailyConsumption?accountNo=$DESCO_ID&meterNo=&dateFrom=$dateFrom&dateTo=$dateTo";
$consumptionResponse = fetchData($consumptionApiUrl);
$consumptionData = json_decode($consumptionResponse, true);


// $consumptions = $consumptionData['data'];
// $totalConsumption = 0;
// $daysCount = count($consumptions);

// foreach ($consumptions as $dailyConsumption) {
//     $totalConsumption += $dailyConsumption['consumedTaka'];
// }
// For testing purposes
$averageConsumption = 670;
//$averageConsumption = $totalConsumption / $daysCount;

echo "Balance is: $balance<br>";
echo "7-Day Average Consumption is: $averageConsumption<br>";

if ($balance < $averageConsumption) {
    $subject = "Low Balance Alert: Your DESCO Prepaid Balance is Low";
    $message = "Your DESCO prepaid balance is currently $balance Tk, which is below the 7-day average consumption of $averageConsumption Tk. Please recharge your account to avoid any inconvenience.";
    
    if (sendEmail($USER_EMAIL, $subject, $message, $SMTP_FROM_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASSWORD)) {
        echo "Email has been sent successfully.";
    } else {
        echo "Failed to send email.";
    }
} else {
    echo "Balance is sufficient, no email sent.";
}
?>