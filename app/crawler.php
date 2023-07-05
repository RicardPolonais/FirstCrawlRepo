<?php

class SiteMapper
{
    protected $startUrl;
    protected $depth;
    protected $parsedStartUrl;
    protected $checkedUrls;
    protected $errorUrls;
    protected $lastCrawlTime;

    public function __construct($startUrl, $depth = 1)
    {
        $this->startUrl = ($startUrl);
        $this->depth = $depth;
        $this->parsedStartUrl = parse_url($startUrl);
    }
    private function newCrawl($url, $depth)
    {
        if ($this->checkIfCrawlNeeded()) {
            // Delete the results from the last crawl (i.e. in temporary storage), if they exist.
            $this->checkedUrls = [];
            $this->errorUrls = [];
            $this->crawlPage($this->startUrl, $this->depth);
            return 1;
        } else {
            return 0;
        }
    }
    private function crawlPage($url, $depth)
    {
        $url = rtrim($url, "/");
        $this->checkedUrls[$url] = true;
        $nodes = $this->grabNodes($this->grabPage($url));
        if ($depth == 0) {
            return;
        }

        // Extract all of the internal hyperlinks, i.e. results.
        if (is_array($nodes) || is_object($nodes)) {
            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');

                if ($newCrawlUrl = $this->newCrawlUrl($href)) {
                    if (!isset($this->checkedUrls[$newCrawlUrl]) and !isset($this->errorUrls[$newCrawlUrl])) {
                        $this->crawlPage($newCrawlUrl, $depth - 1);
                    }
                }
            }
        }
        if ($depth == $this->depth) {
            $this->lastCrawlTime =     time();
        }
    }
    private function newCrawlUrl($href)
    {
        $parsedHref = parse_url(trim($href));
        //read only: (http AND https on absolute path links within startup domain) OR relative paths
        if (($this->parsedStartUrl["host"] == $parsedHref["host"] and ($parsedHref["scheme"] == "http" or $parsedHref["scheme"] == "https"))
            or (empty($parsedHref["host"]) and !empty($parsedHref["path"]))
        ) {
            return $this->parsedStartUrl["scheme"] . "://" . $this->parsedStartUrl["host"] . $parsedHref["path"];
        } else {
            return;
        }
    }
    private function grabPage($url, $timeout = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $output = curl_exec($ch);

        // If an error happens, display an error notice to inform of what happened and guide for what to do.
        if ($errNo = curl_errno($ch)) {
            $this->errorUrls[$url] = $this->curlErrCode($errNo);
            unset($this->checkedUrls[$url]);
        }
        curl_close($ch);

        return $output;
    }

    private function grabNodes($output)
    {
        if (!empty($output)) {
            $dom = new DOMDocument('1.0');
            libxml_use_internal_errors(true);
            $dom->loadHTML($output);
            libxml_clear_errors();
            $nodes = $dom->getElementsByTagName('a');
        }
        return $nodes;
    }
    public function run()
    {
        if ($this->newCrawl($this->startUrl, $this->depth)) {
            // Store results temporarily in the database. *
            // * For storage, you can use a database or the filesystem.
            $this->saveCralwResultsToDB();

            //Create a sitemap.html file that shows the results as a sitemap list structure.
            $this->saveSitemapAsHtml();

            // Save the home page’s .php file as a .html file
            $this->savePageAsHtml($this->startUrl);
        }
    }

    private function checkIfCrawlNeeded()
    {
        [$lastCrawlTime, $checkedUrls, $errorUrls] = $this->getLastResultsFromDB();

        //exit;
        // Only delete the temporary stored results based on time. Normally, we would also delete them when a change in the content happens. But let’s keep it really simple and only delete based on time.

        if ($lastCrawlTime + 1 < time()) {
            return 1;
        } else {
            return 0;
        }
    }
    private function getLastResultsFromDB()
    {
        @include "results/crawl_database.php";
        return [$time, json_decode($checkedUrls), json_decode($errorUrls)];
    }
    private function saveCralwResultsToDB()
    {
        // For storage, you can use a database or the filesystem.
        if (!empty($this->checkedUrls)) {
            $fp = fopen("results/crawl_database_tmp.php", "w") or die("cant open file");
            fputs($fp, "<?php\n");
            fputs($fp, "\$lastCrawlTime = '" .    $this->lastCrawlTime . "';\n");
            fputs($fp, "\$checkedUrls = '" .        json_encode($this->checkedUrls) . "';\n");
            fputs($fp, "\$errorUrls = '" .        json_encode($this->errorUrls) . "';");
            fclose($fp);

            @unlink("results/crawl_database.php");
            @rename("results/crawl_database_tmp.php", "results/crawl_database.php");
        }
    }
    private function saveSitemapAsHtml()
    {
        // Delete the sitemap.html file if it exists.
        @unlink("results/sitemap.html");
        $fp = fopen("results/sitemap.html", "w") or die("cant open file");
        fputs($fp, "<h3>" . $this->startUrl . " sitemap</h3>\n");
        fputs($fp, "<ul> \n");
        
        foreach ($this->checkedUrls as $url => $nothing) {
            fputs($fp, "<li><a href=\"" . $url . "\">" . $url . "</a></li>\n");
        }
        foreach ($this->errorUrls as $url => $err) {
            fputs($fp, "<li><a href=\"" . $url . "\">" . $url . ": error " . $err . "</a></li>\n");
        }
        fputs($fp, "</ul>");
        fclose($fp);
    }
    private function savePageAsHtml($url)
    {
        @unlink("results/phpPage.html");
        $fp = fopen("results/phpPage.html", "w") or die("cant open file");
        fputs($fp, "" . $this->grabPage($url) . "");
        fclose($fp);
    }
    private function curlErrCode($errNo)
    {
        $curl_errcode = [
        1  => "CURLE_UNSUPPORTED_PROTOCOL",
        2  => "CURLE_FAILED_INIT",
        3  => "CURLE_URL_MALFORMAT",
        4  => "CURLE_URL_MALFORMAT_USER",
        5  => "CURLE_COULDNT_RESOLVE_PROXY",
        6  => "CURLE_COULDNT_RESOLVE_HOST",
        7  => "CURLE_COULDNT_CONNECT",
        8  => "CURLE_FTP_WEIRD_SERVER_REPLY",
        9  => "CURLE_FTP_ACCESS_DENIED",
        10 => "CURLE_FTP_USER_PASSWORD_INCORRECT",
        11 => "CURLE_FTP_WEIRD_PASS_REPLY",
        12 => "CURLE_FTP_WEIRD_USER_REPLY",
        13 => "CURLE_FTP_WEIRD_PASV_REPLY",
        14 => "CURLE_FTP_WEIRD_227_FORMAT",
        15 => "CURLE_FTP_CANT_GET_HOST",
        16 => "CURLE_FTP_CANT_RECONNECT",
        17 => "CURLE_FTP_COULDNT_SET_BINARY",
        18 => "CURLE_FTP_PARTIAL_FILE or CURLE_PARTIAL_FILE",
        19 => "CURLE_FTP_COULDNT_RETR_FILE",
        20 => "CURLE_FTP_WRITE_ERROR",
        21 => "CURLE_FTP_QUOTE_ERROR",
        22 => "CURLE_HTTP_NOT_FOUND or CURLE_HTTP_RETURNED_ERROR",
        23 => "CURLE_WRITE_ERROR",
        24 => "CURLE_MALFORMAT_USER",
        25 => "CURLE_FTP_COULDNT_STOR_FILE",
        26 => "CURLE_READ_ERROR",
        27 => "CURLE_OUT_OF_MEMORY",
        28 => "CURLE_OPERATION_TIMEDOUT or CURLE_OPERATION_TIMEOUTED",
        29 => "CURLE_FTP_COULDNT_SET_ASCII",
        30 => "CURLE_FTP_PORT_FAILED",
        31 => "CURLE_FTP_COULDNT_USE_REST",
        32 => "CURLE_FTP_COULDNT_GET_SIZE",
        33 => "CURLE_HTTP_RANGE_ERROR",
        34 => "CURLE_HTTP_POST_ERROR",
        35 => "CURLE_SSL_CONNECT_ERROR",
        36 => "CURLE_BAD_DOWNLOAD_RESUME or CURLE_FTP_BAD_DOWNLOAD_RESUME",
        37 => "CURLE_FILE_COULDNT_READ_FILE",
        38 => "CURLE_LDAP_CANNOT_BIND",
        39 => "CURLE_LDAP_SEARCH_FAILED",
        40 => "CURLE_LIBRARY_NOT_FOUND",
        41 => "CURLE_FUNCTION_NOT_FOUND",
        42 => "CURLE_ABORTED_BY_CALLBACK",
        43 => "CURLE_BAD_FUNCTION_ARGUMENT",
        44 => "CURLE_BAD_CALLING_ORDER",
        45 => "CURLE_HTTP_PORT_FAILED",
        46 => "CURLE_BAD_PASSWORD_ENTERED",
        47 => "CURLE_TOO_MANY_REDIRECTS",
        48 => "CURLE_UNKNOWN_TELNET_OPTION",
        49 => "CURLE_TELNET_OPTION_SYNTAX",
        50 => "CURLE_OBSOLETE",
        51 => "CURLE_SSL_PEER_CERTIFICATE",
        52 => "CURLE_GOT_NOTHING",
        53 => "CURLE_SSL_ENGINE_NOTFOUND",
        54 => "CURLE_SSL_ENGINE_SETFAILED",
        55 => "CURLE_SEND_ERROR",
        56 => "CURLE_RECV_ERROR",
        57 => "CURLE_SHARE_IN_USE",
        58 => "CURLE_SSL_CERTPROBLEM",
        59 => "CURLE_SSL_CIPHER",
        60 => "CURLE_SSL_CACERT",
        61 => "CURLE_BAD_CONTENT_ENCODING",
        62 => "CURLE_LDAP_INVALID_URL",
        63 => "CURLE_FILESIZE_EXCEEDED",
        64 => "CURLE_FTP_SSL_FAILED",
        79 => "CURLE_SSH"
        ];
        return $curl_errcode[$errNo];
    }
}