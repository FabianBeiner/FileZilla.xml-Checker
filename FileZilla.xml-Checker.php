<pre>
 _____ _ _     _____ _ _ _                 _     _____ _           _
|   __|_| |___|__   |_| | |___   _ _ _____| |___|     | |_ ___ ___| |_ ___ ___
|   __| | | -_|   __| | | | .'|_|_'_|     | |___|   --|   | -_|  _| '_| -_|  _|
|__|  |_|_|___|_____|_|_|_|__,|_|_,_|_|_|_|_|   |_____|_|_|___|___|_,_|___|_|
                - a PHP-based FileZilla Server Status Checker -

<?php
/**
* FileZilla.xml-Checker -- a PHP-based FileZilla Server Status Checker
*
* This script tests all available servers found in your FileZilla.xml file.
* So you can easily determinate, which of the sites are online and which are
* offline.
*
* This script is made possible through FileZilla’s crappy security definitions.
* I mean, seriously, saving passwords in plaintext? Sucks.
*
* Here is my advice: Since FileZilla is an awesome, free client, use it.
* However, keep it in a crypto-container, created with (e.g.) TrueCrypt.
*
*
* @author Fabian Beiner <mail@fabian-beiner.de>
* @copyright 2011 -- Fabian Beiner
* @license Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
* (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
* @link http://fabian-beiner.de
* @version 1.0
*/

function testFileZillaSites($strXml = 'FileZilla.xml', $bolShowPasswords = false, $intTimeout = 15) {
    //
    if (!function_exists('curl_init')) {
        echo '<b>Error:</b> This script requires the “Client URL Library” (cURL).';
        return false;
    }
    // If there is no filename given, use the default FileZilla.xml.
    $strXml = ($strXml) ? trim($strXml) : 'FileZilla.xml';
    // Check, if the file exists and is readable.
    if (is_readable($strXml)) {
        // Turn on output buffering.
        ob_start();
        // Load the XML.
        $oXML      = simplexml_load_file($strXml);
        // Find servers in the XML.
        $arrResult = $oXML->xpath('//Server');

        echo '<b>Found ' . count($arrResult) . ' servers in ' . $strXml . '. Testing each server …</b><br><br>';

        // Counter.
        $intOnline  = 0;
        $intOffline = 0;

        foreach($arrResult as $oResult) {
            // Which protocol is the server using?
            switch ($oResult->Protocol) {
                case '1':
                    $strProtocol = 'sftp';
                    break;
                default:
                    $strProtocol = 'ftp';
            }

            $strCon  = $strProtocol . '://';

            $pass = $oResult->Pass;

            if ('base64' == (string)$oResult->Pass->attributes()->encoding) {
                $pass = base64_decode( $oResult->Pass );
            }


            if ($oResult->User && $pass) {
                $strCon .= $oResult->User . ':' . $pass . '@';
            }
            $strCon .= $oResult->Host . ':' . $oResult->Port;

            $strConPrint = $strCon;
            if (!$bolShowPasswords) {
                $strConPrint = str_replace(':' . $pass . '@', ':' . str_repeat('*', strlen($pass)) . '@', $strConPrint);
            }
            echo '- <b>' . $oResult->Name . '</b> (' . $strConPrint . ') … ';

            // Connecting to the server.
            $oCurl = curl_init($strCon);
            curl_setopt_array($oCurl, array (
                                            CURLOPT_VERBOSE => false
                                           ,CURLOPT_FRESH_CONNECT => true
                                           ,CURLOPT_RETURNTRANSFER => true
                                           ,CURLOPT_TIMEOUT => (is_int($intTimeout) ? $intTimeout : 10)
                                           ,CURLOPT_CONNECTTIMEOUT => 0
                                           ,CURLOPT_SSL_VERIFYPEER => false
                                           ,CURLOPT_SSL_VERIFYHOST => false
                                           ,CURLOPT_FTP_SSL => CURLFTPSSL_TRY
                                           ,CURLOPT_UPLOAD => false
                                            ));
            $strReturn  = curl_exec($oCurl);
            $intCurlErr = curl_errno($oCurl);
            $strCurlErr = curl_error($oCurl);
            curl_close($oCurl);

            // Was there any error?
            if ($intCurlErr > 0) {
                echo '<span style="color:red;">failed!</span> (' .  $strCurlErr . ')<br>';
                $intOffline++;
            }
            else {
                echo '<span style="color:green;">successful!</span><br>';
                $intOnline++;
            }
            // Flush the output buffer.
            ob_flush();
            flush();
        }
        echo '<br><br><b>Tested ' . count($arrResult) . ' servers. ' . $intOnline . ' reachable, ' . $intOffline . ' not.</b>';
    }
    else {
        echo '<b>Error:</b> Could not open <em>' . $strXml . '</em> for reading!';
    }

}

// Immediately run the function.
testFileZillaSites('sites.xml', false, 10);
?>
</pre>
