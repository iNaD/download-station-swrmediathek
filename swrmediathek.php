<?php

/**
 * @author Daniel Gehn <me@theinad.com>
 * @version 0.1
 * @copyright 2015 Daniel Gehn
 * @license http://opensource.org/licenses/MIT Licensed under MIT License
 */

class SynoFileHostingSWRMediathek {
    private $Url;
    private $Username;
    private $Password;
    private $HostInfo;

    private $LogPath = '/tmp/swr-mediathek.log';
    private $LogEnabled = false;

    protected static $qualities = array(
        's' => 1,
        'm' => 2,
        'l' => 3,
        'xl' => 4
    );

    public function __construct($Url, $Username = '', $Password = '', $HostInfo = '') {
        $this->Url = $Url;
        $this->Username = $Username;
        $this->Password = $Password;
        $this->HostInfo = $HostInfo;

        $this->DebugLog("URL: $Url");
    }

    //This function returns download url.
    public function GetDownloadInfo() {
        $ret = FALSE;

        $this->DebugLog("GetDownloadInfo called");

        $ret = $this->Download();

        return $ret;
    }

    public function onDownloaded()
    {
    }

    public function Verify($ClearCookie = '')
    {
        $this->DebugLog("Verifying User");

        return USER_IS_PREMIUM;
    }

    //This function gets the download url
    private function Download() {
        $this->DebugLog("Getting download url $this->Url");

        preg_match('#show=([0-9a-z\-]+)#i', $this->Url, $match);

        if(count($match) == 0)
        {
            $this->DebugLog("Couldn't identify hash");
            return false;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://www.swrmediathek.de/fbplayerparams/" . $match[1] . "/clips.xml");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $rawXML = curl_exec($curl);

        if(!$rawXML)
        {
            $this->DebugLog("Failed to retrieve Website. Error Info: " . curl_error($curl));
            return false;
        }

        curl_close($curl);

        if(preg_match('#media:content\s*url="(.*?)"#si', $rawXML, $match) === 1)
        {
            $baseurl = $match[1];
            if(preg_match('#\.([a-z]?[a-z])\.mp4#i', $baseurl, $match) === 1)
            {
                $bestUrl = $baseurl;
                $bestQuality = $match[1];
                $bestRating = isset(self::$qualities[$match[1]]) ? self::$qualities[$match[1]] : 1;

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_NOBODY, true);

                foreach(self::$qualities as $quality => $rating)
                {
                    if($rating > $bestRating)
                    {

                        $newUrl = str_replace('.' . $bestQuality . '.mp4', '.' . $quality . '.mp4', $bestUrl);

                        curl_setopt($curl, CURLOPT_URL, $newUrl);

                        curl_exec($curl);

                        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                        if($http_status === 200)
                        {
                            $bestUrl = $newUrl;
                            $bestQuality = $quality;
                            $bestRating = $rating;
                        }

                    }
                }

                curl_close($curl);
                unset($curl);

                $DownloadInfo = array();
                $DownloadInfo[DOWNLOAD_URL] = trim($bestUrl);

                return $DownloadInfo;
            }

            $this->DebugLog("Couldn't identify media quality" . $baseurl);

            $DownloadInfo = array();
            $DownloadInfo[DOWNLOAD_URL] = trim($baseurl);

            return $DownloadInfo;
        }

        $this->DebugLog("Couldn't identify media file" . PHP_EOL . $rawXML);

        return FALSE;
    }

    private function DebugLog($message)
    {
        if($this->LogEnabled === true)
        {
            file_put_contents($this->LogPath, $message . "\n", FILE_APPEND);
        }
    }
}
?>
