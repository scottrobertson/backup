<?php

// NOTE: You should be using Composer's global autoloader.  But just so these examples
// work for people who don't have Composer, we'll use the library's "autoload.php".
require_once __DIR__.'/../lib/Dropbox/autoload.php';

use \Dropbox as dbx;

$appInfoFile = __DIR__."/web-file-browser.app";

session_start();

$req = $_SERVER['SCRIPT_NAME'];

if ($req === "/") {
    $dbxClient = getClient();

    if ($dbxClient === false) {
        header("Location: /dropbox-auth-start");
        exit;
    }

    $path = "/";
    if (isset($_GET['path'])) $path = $_GET['path'];

    if (isset($_GET['dl'])) {
        passFileToBrowser($dbxClient, $path);
    }
    else {
        $entry = $dbxClient->getMetadataWithChildren($path);

        if ($entry['is_dir']) {
            echo renderFolder($entry);
        }
        else {
            echo renderFile($entry);
        }
    }
}
else if ($req === "/dropbox-auth-start") {
    $authorizeUrl = getWebAuth()->start();
    header("Location: $authorizeUrl");
}
else if ($req === "/dropbox-auth-finish") {
    try {
        list($accessToken, $userId, $urlState) = getWebAuth()->finish($_GET);
        assert($urlState === null);
        unset($_SESSION['dropbox-auth-csrf-token']);
    }
    catch (dbx\WebAuthException_BadRequest $ex) {
        error_log("/dropbox-auth-finish: bad request: " . $ex->getMessage());
        // Respond with an HTTP 400 and display error page...
        exit;
    }
    catch (dbx\WebAuthException_BadState $ex) {
        // Auth session expired.  Restart the auth process.
        header('Location: /dropbox-auth-start');
        exit;
    }
    catch (dbx\WebAuthException_Csrf $ex) {
        error_log("/dropbox-auth-finish: CSRF mismatch: " . $ex->getMessage());
        // Respond with HTTP 403 and display error page...
        exit;
    }
    catch (dbx\WebAuthException_NotApproved $ex) {
        echo renderHtmlPage("Not Authorized?", "Why not, bro?");
        exit;
    }
    catch (dbx\WebAuthException_Provider $ex) {
        error_log("/dropbox-auth-finish: unknown error: " . $ex->getMessage());
        exit;
    }
    catch (dbx\Exception $ex) {
        error_log("/dropbox-auth-finish: error communicating with Dropbox API: " . $ex->getMessage());
        exit;
    }

    // NOTE: A real web app would store the access token in a database.
    $_SESSION['access-token'] = $accessToken;

    echo renderHtmlPage("Authorized!", "Auth complete, <a href='/'>click here</a> to browse");
}
else if ($req === "/unlink") {
    // "Forget" the access token.
    unset($_SESSION['access-token']);
    header("Location: /");
}
else if ($req === "/upload") {
    if (empty($_FILES['file']['name'])) {
        echo renderHtmlPage("Error", "Please choose a file to upload");
        exit;
    }

    if (!empty($_FILES['file']['error'])) {
        echo renderHtmlPage("Error", "Error ".$_FILES['file']['error']." uploading file.  See <a href='http://php.net/manual/en/features.file-upload.errors.php'>the docs</a> for details");
        exit;
    }

    $dbxClient = getClient();

    $remoteDir = "/";
    if (isset($_POST['folder'])) $remoteDir = $_POST['folder'];

    $remotePath = rtrim($remoteDir, "/")."/".$_FILES['file']['name'];

    $fp = fopen($_FILES['file']['tmp_name'], "rb");
    $result = $dbxClient->uploadFile($remotePath, dbx\WriteMode::add(), $fp);
    fclose($fp);
    $str = print_r($result, TRUE);
    echo renderHtmlPage("Uploading File", "Result: <pre>$str</pre>");
}
else {
    echo renderHtmlPage("Bad URL", "No handler for $req");
    exit;
}

function renderFolder($entry)
{
    // TODO: Add a token to counter CSRF attacks.
    $form = <<<HTML
        <form action='/upload' method='post' enctype='multipart/form-data'>
        <label for='file'>Upload file:</label> <input name='file' type='file'/>
        <input type='submit' value='Upload'/>
        <input name='folder' type='hidden' value='$entry[path]'/>
        </form>
HTML;

    $listing = '';
    foreach($entry['contents'] as $child) {
        $cp = $child['path'];
        $cn = basename($cp);
        if ($child['is_dir']) $cn .= '/';

        $cp = urlencode($cp);
        $listing .= "<div><a style='text-decoration: none' href='/?path=$cp'>$cn</a></div>";
    }

    return renderHtmlPage("Folder: $entry[path]", $form.$listing);
}

function getAppConfig()
{
    global $appInfoFile;

    try {
        $appInfo = dbx\AppInfo::loadFromJsonFile($appInfoFile);
    }
    catch (dbx\AppInfoLoadException $ex) {
        error_log("Unable to load \"$appInfoFile\": " . $ex->getMessage());
        die;
    }

    $clientIdentifier = "examples-web-file-browser";
    $userLocale = null;

    return array($appInfo, $clientIdentifier, $userLocale);
}

function getClient()
{
    if(!isset($_SESSION['access-token'])) {
        return false;
    }

    list($appInfo, $clientIdentifier, $userLocale) = getAppConfig();

    try {
        $accessToken = $_SESSION['access-token'];
        $dbxClient = new dbx\Client($accessToken, $clientIdentifier, $userLocale, $appInfo->getHost());
    }
    catch (Exception $e) {
        error_log("Error in getClient: ".$e->getMessage());
        return false;
    }

    return $dbxClient;
}

function getWebAuth()
{
    list($appInfo, $clientIdentifier, $userLocale) = getAppConfig();
    $redirectUri = getBaseUrl()."/dropbox-auth-finish";
    $csrfTokenStore = new dbx\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token');
    return new dbx\WebAuth($appInfo, $clientIdentifier, $redirectUri, $csrfTokenStore, $userLocale);
}

function renderFile($entry)
{
    $metadataStr = print_r($entry, TRUE);
    $path = urlencode($entry['path']);
    $body = <<<HTML
        <pre>$metadataStr</pre>
        <a href="/?path=$path&dl=true">Download this file</a>
HTML;

    return renderHtmlPage("File: ".$entry['path'], $body);
}

function passFileToBrowser(dbx\Client $dbxClient, $path)
{
    $fd = tmpfile();
    $metadata = $dbxClient->getFile($path, $fd);

    header("Content-type: $metadata[mime_type]");
    fseek($fd, 0);
    fpassthru($fd);
    fclose($fd);
}

function renderHtmlPage($title, $body)
{
    return <<<HTML
    <html>
        <head>
            <title>$title</title>
        </head>
        <body>
            <h1>$title</h1>
            $body
        </body>
    </html>
HTML;
}

function getBaseUrl()
{
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]";
}
