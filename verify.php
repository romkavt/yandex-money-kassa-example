<?php
/**
 * Проверка подписи запроса при взаимодействии по схеме XML/PKCS#7
 * @return ассоциативный массив или null, в случае ошибки разбора
 */
private function verifySign()
{
    $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
    $certificate = 'yamoney.pem';
    $process = proc_open('openssl smime -verify -inform PEM -nointern -certfile ' . $certificate . ' -CAfile ' . $certificate,
        $descriptorspec, $pipes);
    if (is_resource($process)) {
        //Получим данные из тела запроса
        $data = file_get_contents("php://input");
        fwrite($pipes[0], $data);
        fclose($pipes[0]);
        $content = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $resCode = proc_close($process);
        if ($resCode != 0) {
            return false;
        } else {
            $this->log("Row xml: " . $content);
            $xml = simplexml_load_string($content);
            $array = json_decode(json_encode($xml), TRUE);
            return $array["@attributes"];
        }
    }
    return false;
}
?>