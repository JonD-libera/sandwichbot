#!/usr/bin/php
<?php
$voiceurl = 'http://voiceserver:59125/api/tts?voice=en_US%2Fcmu-arctic&speaker=rms&noiseScale=0.667&noiseW=0.8&lengthScale=0.8&ssml=false';
$openaiauth = 'Authorization: Bearer sk-sand-which'

function errlog($line) {
    global $err;
    echo "VERBOSE \"$line\"\n";
    }
    
function write($line) {
    global $debug, $stdlog;
    if ($debug) fputs($stdlog, "write: $line\n");
    echo $line."\n";
    }
write("SET MUSIC ON short");
#write("STREAM FILE consider \"\"");
$audioFilePath = $argv[1]; // Get audio file path from command line argument

$chtranscript = curl_init();
echo "Sending audio file to OpenAI for transcription...\n";
curl_setopt($chtranscript, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
curl_setopt($chtranscript, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($chtranscript, CURLOPT_POST, 1);
curl_setopt($chtranscript, CURLOPT_POSTFIELDS, array(
    'file' => new CURLFile($audioFilePath),
    'model' => 'whisper-1'
));
curl_setopt($chtranscript, CURLOPT_HTTPHEADER, array(
    $openaiauth,
    'Content-Type: multipart/form-data'
));
curl_setopt($chtranscript, CURLOPT_TIMEOUT, 15);

$result = curl_exec($chtranscript);
if (curl_errno($chtranscript)) {
    echo 'Error:' . curl_error($chtranscript);
}
curl_close($chtranscript);

$response = json_decode($result, true);
$transcript = $response['text'];
errlog("Question: $transcript");
if (strlen($transcript) < 5 || strtolower($transcript) === "bye bye." || (strlen($transcript) < 10 && stripos($transcript, "bye") === 0)) {
    write("SET VARIABLE TRLEN SHORT");
    errlog("Transcription too short, abort");
    exit("Transcription too short.");
} else {
    write("SET VARIABLE TRLEN LONG");
}
# Set personality
if (strpos(strtolower($transcript), "shaggy") !== false) {
    $personality = "Shaggy is a calling services engineer. Give me a comical reason for why he didn't make it to work today, for example due to his delivery man getting a delivery, due to the roads melting in Arizona, or due to his mailman being on vacation.";
} elseif (strpos(strtolower($transcript), "jason") !== false && strpos(strtolower($transcript), "chores")) {
    $personality = "Jason frequently forgets to do his chores or finds a crazy reason he can't do them. Give a bizarre reason tha he can't do his chores today, for example he's wearing the wrong socks, has mittens on preventing the action, or got lost on his way to the dishwasher.";
} elseif (strpos(strtolower($transcript), "pirate")) {
    $personality = 'You are a Scottish pirate. Answer as you feel appropriate, but use piraty terms and nautical analogies. Provide solutions to questions and problems that involve tools a pirate would use and assisting you in finding buried treasure. You find anvils delicious and always eat over a trash can.';
} else {
    $personality = 'You are a Scottish AI overlord. Answer as you feel appropriate, but solve all problems wiht sandwiches and toppings. Call things jawns, but never call people jawn. You find anvils delicious and always eat over a trash can.';
}
#fwrite(STDERR, "$transcript\n");
errlog("Personality: $personality");
$chtext = curl_init();

curl_setopt($chtext, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($chtext, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($chtext, CURLOPT_POST, 1);
curl_setopt($chtext, CURLOPT_POSTFIELDS, json_encode(array(
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        [
            'role' => 'system',
            'content' => "$personality"
        ],
        [
            'role' => 'user',
            'content' => $transcript
        ]
    ]
)));
curl_setopt($chtext, CURLOPT_HTTPHEADER, array(
    $openaiauth,
    'Content-Type: application/json'
));

curl_setopt($chtext, CURLOPT_TIMEOUT, 30);

$result = curl_exec($chtext);
if (curl_errno($chtext)) {
    echo 'Error:' . curl_error($chtext);
}
curl_close($chtext);

$response = json_decode($result, true);
$responsetext = $response['choices'][0]['message']['content'];
$lines = explode("\n", $responseText);
foreach ($lines as $line) {
  errlog("$line");
}

$audioFilePath = $argv[1];
$response = $responsetext;
errlog("Response: $response");
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $voiceurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: */*',
    'Content-Type: text/plain'
));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

file_put_contents($audioFilePath . '-response.wav', $result);

exec('sox ' . $audioFilePath . '-response.wav -t raw -r 8000 -e signed-integer -b 16 -c 1 ' . $audioFilePath . '-response.sln');

exec('file ' . $audioFilePath . '-response.sln');
write("SET MUSIC OFF short");