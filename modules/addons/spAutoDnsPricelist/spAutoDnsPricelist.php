<?php

declare(strict_types=1);

/*
 * AutoDNS Price List Addon
 *
 * Fetches and displays the AutoDNS XML price list in the WHMCS admin area.
 */

if (! defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

if (! function_exists('spAutoDnsPricelist_lang')) {

    function spAutoDnsPricelist_lang() : array
    {

        $sysLanguage = Capsule::table('tblconfiguration')->where('setting', 'Language')
            ->value('value');
        $sysLanguage = is_string($sysLanguage) && $sysLanguage !== '' ? $sysLanguage : 'english';

        if (defined('ADMINAREA')) {
            if (isset($_SESSION['adminid']) && (int) $_SESSION['adminid'] > 0) {
                $adminLanguage = Capsule::table('tbladmins')->where('id', (int) $_SESSION['adminid'])
                    ->value('language');
                if (is_string($adminLanguage) && $adminLanguage !== '') {
                    $sysLanguage = $adminLanguage;
                }
            }
        } elseif (isset($_SESSION['uid']) && (int) $_SESSION['uid'] > 0) {
            $clientLanguage = Capsule::table('tblclients')->where('id', (int) $_SESSION['uid'])
                ->value('language');
            if (is_string($clientLanguage) && $clientLanguage !== '') {
                $sysLanguage = $clientLanguage;
            }
        }

        if (isset($_SESSION['Language']) && is_string($_SESSION['Language']) && $_SESSION['Language'] !== '') {
            $sysLanguage = $_SESSION['Language'];
        }

        $safeLang = preg_replace('/[^a-zA-Z0-9_-]/', '', basename($sysLanguage));
        if ($safeLang !== '') {
            $langFile = __DIR__ . '/lang/' . $safeLang . '.php';
            if (file_exists($langFile)) {
                require $langFile;

                return $_ADDONLANG ?? [];
            }
        }

        $fallbackFile = __DIR__ . '/lang/english.php';
        if (file_exists($fallbackFile)) {
            require $fallbackFile;
            return $_ADDONLANG ?? [];
        }
        return [];
    }

}

if (! function_exists('spAutoDnsPricelist_config')) {

    function spAutoDnsPricelist_config() : array
    {

        $_ADDONLANG = spAutoDnsPricelist_lang();
        return [
            'name'        => 'DD: ' . $_ADDONLANG['TITLE'],
            'description' => $_ADDONLANG['DESC'],
            'version'     => '1.0.0',
            'author'      => '<a href="https://www.datadachs.eu/whmcs/" target="_blank" rel="noopener">DataDachs</a>',
            'language'    => 'english',
            'fields'      => [
                'username'           => [
                    'FriendlyName' => $_ADDONLANG['USERNAME'],
                    'Type'         => 'text',
                    'Size'         => '50',
                    'Description'  => $_ADDONLANG['USERNAME_DESC'],
                    'Default'      => '',
                ],
                'password'           => [
                    'FriendlyName' => $_ADDONLANG['PASSWORD'],
                    'Type'         => 'password',
                    'Size'         => '50',
                    'Description'  => $_ADDONLANG['PASSWORD_DESC'],
                    'Default'      => '',
                ],
                'drop_on_deactivate' => [
                    'FriendlyName' => $_ADDONLANG['DROP_TABLES'],
                    'Type'         => 'yesno',
                    'Description'  => $_ADDONLANG['DROP_TABLES_DESC'],
                    'Default'      => '',
                ],
            ],
        ];
    }

}

if (! function_exists('spAutoDnsPricelist_activate')) {

    function spAutoDnsPricelist_activate() : array
    {

        return [
            'status'      => 'success',
            'description' => 'Activated successfully.',
        ];
    }

}

if (! function_exists('spAutoDnsPricelist_deactivate')) {

    function spAutoDnsPricelist_deactivate() : array
    {

        return [
            'status'      => 'success',
            'description' => 'Deactivated successfully.',
        ];
    }

}

require_once __DIR__ . '/lib/Views/DDADStylesTrait.php';
require_once __DIR__ . '/lib/Renderer.php';

if (! function_exists('spAutoDnsPricelist_runHealthCheck')) {

    function spAutoDnsPricelist_runHealthCheck(array $vars) : array
    {

        $checks = [];

        // PHP Version check
        $phpPass  = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = [
            'title'  => 'PHP Version',
            'desc'   => 'Requires PHP 8.2 or higher. Current version: ' . PHP_VERSION,
            'status' => $phpPass ? 'pass' : 'fail'
        ];

        // Curl check
        $curlLoaded = extension_loaded('curl');
        $checks[]   = [
            'title'  => 'CURL Extension',
            'desc'   => 'Checks if PHP curl extension is loaded.',
            'status' => $curlLoaded ? 'pass' : 'fail'
        ];

        // API Credentials check
        $hasUsername = ! empty($vars['username']);
        $hasPassword = ! empty($vars['password']);
        $checks[]    = [
            'title'  => 'AutoDNS Credentials',
            'desc'   => 'Checks if AutoDNS API username and password settings are configured.',
            'status' => ($hasUsername && $hasPassword) ? 'pass' : 'fail'
        ];

        return $checks;
    }

}

if (! function_exists('spAutoDnsPricelist_runTldSyncCheck')) {

    function spAutoDnsPricelist_runTldSyncCheck(array $l) : array
    {

        $whmcsRoot           = defined('ROOTDIR') ? rtrim((string) ROOTDIR, '/\\') : dirname(__DIR__, 3);
        $registrarDir        = $whmcsRoot . '/modules/registrars/InterNetX';
        $registrarFile       = $registrarDir . '/InterNetX.php';
        $registrarModuleFile = $registrarDir . '/app/RegistrarModule.php';

        $checks       = [];
        $moduleExists = is_file($registrarFile) && is_readable($registrarFile);
        $checks[]     = [
            'title'  => $l['TLD_SYNC_CHECK_MODULE'],
            'desc'   => $moduleExists ? $l['TLD_SYNC_CHECK_MODULE_OK'] : $l['TLD_SYNC_CHECK_MODULE_MISSING'],
            'status' => $moduleExists ? 'pass' : 'warn',
        ];

        $tlsStatus = 'warn';
        $tlsDesc   = $l['TLD_SYNC_CHECK_TLS_NA'];

        if ($moduleExists) {
            $content          = (string) file_get_contents($registrarFile);
            $hasGetTldPricing = preg_match('/function\s+InterNetX_GetTldPricing\s*\(/i', $content) === 1;
            $checks[]         = [
                'title'  => $l['TLD_SYNC_CHECK_FUNC'],
                'desc'   => $hasGetTldPricing ? $l['TLD_SYNC_CHECK_FUNC_OK'] : $l['TLD_SYNC_CHECK_FUNC_MISSING'],
                'status' => $hasGetTldPricing ? 'pass' : 'fail',
            ];

            if ($hasGetTldPricing) {
                $pos             = stripos($content, 'InterNetX_GetTldPricing');
                $funcBody        = $pos !== false ? substr($content, $pos) : '';
                $hasInsecurePeer = preg_match('/CURLOPT_SSL_VERIFYPEER\s*(?:=>|,)\s*(?:false|0)\b/i', $funcBody) === 1;
                $hasInsecureHost = preg_match('/CURLOPT_SSL_VERIFYHOST\s*(?:=>|,)\s*(?:false|0)\b/i', $funcBody) === 1;
                $hasSecurePeer   = preg_match('/CURLOPT_SSL_VERIFYPEER\s*(?:=>|,)\s*(?:true|1)\b/i', $funcBody) === 1;
                $hasSecureHost   = preg_match('/CURLOPT_SSL_VERIFYHOST\s*(?:=>|,)\s*2\b/i', $funcBody) === 1;

                if (! $hasInsecurePeer && ! $hasInsecureHost && $hasSecurePeer && $hasSecureHost) {
                    $tlsStatus = 'pass';
                    $tlsDesc   = $l['TLD_SYNC_CHECK_TLS_OK'];
                } else {
                    $tlsStatus = 'fail';
                    $tlsDesc   = $l['TLD_SYNC_CHECK_TLS_FAIL'];
                }
            }
        } else {
            $checks[] = [
                'title'  => $l['TLD_SYNC_CHECK_FUNC'],
                'desc'   => $l['TLD_SYNC_CHECK_FUNC_MISSING'],
                'status' => 'warn',
            ];
        }

        $checks[] = [
            'title'  => $l['TLD_SYNC_CHECK_TLS'],
            'desc'   => $tlsDesc,
            'status' => $tlsStatus,
        ];

        $hasXmlFields = false;
        if (is_file($registrarModuleFile) && is_readable($registrarModuleFile)) {
            $rmContent    = (string) file_get_contents($registrarModuleFile);
            $hasXmlFields = strpos($rmContent, 'serverXMLUsername') !== false
                && strpos($rmContent, 'serverXMLPassword') !== false;
        }
        $checks[] = [
            'title'  => $l['TLD_SYNC_CHECK_CONFIG'],
            'desc'   => $hasXmlFields ? $l['TLD_SYNC_CHECK_CONFIG_OK'] : $l['TLD_SYNC_CHECK_CONFIG_MISSING'],
            'status' => $hasXmlFields ? 'pass' : ($moduleExists ? 'fail' : 'warn'),
        ];

        return $checks;
    }

}

if (! function_exists('spAutoDnsPricelist_getTldSyncSnippets')) {

    function spAutoDnsPricelist_getTldSyncSnippets() : array
    {

        $interNetXSnippet = <<<'PHP'
            use WHMCS\Domain\TopLevel\ImportItem;
            use WHMCS\Results\ResultsList;

            function InterNetX_GetTldPricing(array $params)
            {
                $dd_username = (string) ($params['serverXMLUsername'] ?? '');
                $dd_password = (string) ($params['serverXMLPassword'] ?? '');
                $dd_context  = str_replace(["\r", "\n"], '', (string) ($params['serverContext'] ?? ''));

                if ($dd_username === '' || $dd_password === '') {
                    return ['error' => 'AutoDNS XML credentials are not configured.'];
                }

                if (!extension_loaded('curl')) {
                    return ['error' => 'PHP cURL extension is not available.'];
                }

                $dd_url = 'https://api.autodns.com/v1/document/price_list.xml';

                $ch = curl_init();
                if ($ch === false) {
                    logActivity('InterNetX_GetTldPricing cURL initialization failed.');
                    return ['error' => 'Connection error while retrieving price list.'];
                }

                curl_setopt_array($ch, [
                    CURLOPT_URL             => $dd_url,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
                    CURLOPT_USERPWD         => $dd_username . ':' . $dd_password,
                    CURLOPT_HTTPHEADER      => [
                        'Accept: application/xml',
                        'Content-Type: application/xml',
                        'X-Context: ' . $dd_context,
                    ],
                    CURLOPT_SSL_VERIFYPEER  => true,
                    CURLOPT_SSL_VERIFYHOST  => 2,
                    CURLOPT_SSLVERSION      => CURL_SSLVERSION_TLSv1_2,
                    CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
                    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                    CURLOPT_FOLLOWLOCATION  => false,
                    CURLOPT_CONNECTTIMEOUT  => 10,
                    CURLOPT_TIMEOUT         => 30,
                ]);

                $response = curl_exec($ch);

                if (curl_errno($ch) !== 0 || $response === false) {
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    logActivity('InterNetX_GetTldPricing cURL error: ' . $curlError);
                    return ['error' => 'Connection error while retrieving price list.'];
                }

                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (($httpCode === 200 || $httpCode === 406) && strpos((string) $response, '<?xml') !== false) {
                    $prevUseErrors = libxml_use_internal_errors(true);
                    $xml = simplexml_load_string((string) $response, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
                    libxml_clear_errors();
                    libxml_use_internal_errors($prevUseErrors);

                    if ($xml === false || !isset($xml->prices->domain)) {
                        logActivity('InterNetX_GetTldPricing XML parse error for HTTP status ' . $httpCode);
                        return ['error' => 'Error retrieving price list. Invalid XML response.'];
                    }

                    $results = new ResultsList();

                    foreach ($xml->prices->domain as $domain) {
                        $domainLabel = '.' . (string) $domain['label'];
                        $prices      = [
                            'create'   => 0.0,
                            'renew'    => 0.0,
                            'transfer' => 0.0,
                            'restore'  => 0.0,
                        ];
                        $currency    = 'EUR';

                        foreach ($domain->businessCase as $case) {
                            $caseLabel = (string) $case['label'];
                            if (array_key_exists($caseLabel, $prices) && isset($case->price['amount'])) {
                                $prices[$caseLabel] = (float) $case->price['amount'];
                            }
                        }

                        $item = (new ImportItem())
                            ->setExtension($domainLabel)
                            ->setMinYears(1)
                            ->setRegisterPrice($prices['create'])
                            ->setRenewPrice($prices['renew'])
                            ->setTransferPrice($prices['transfer'])
                            ->setRedemptionFeePrice($prices['restore'])
                            ->setCurrency($currency);

                        $results[] = $item;
                    }

                    return $results;
                }

                logActivity('InterNetX_GetTldPricing HTTP error: status ' . $httpCode);
                return ['error' => 'Error retrieving price list. HTTP Code: ' . $httpCode];
            }
            PHP;

        $registrarModuleSnippet = <<<'PHP'
            "serverXMLUsername" => [
                'FriendlyName' => 'XML Pricelist: Username',
                'Type'         => 'text',
                'Size'         => '50',
                'Description'  => 'Enter your username here.',
            ],
            "serverXMLPassword" => [
                'FriendlyName' => 'XML Pricelist: Password',
                'Type'         => 'password',
                'Size'         => '50',
                'Description'  => 'Enter your password here.',
            ],
            PHP;

        return [
            'internetx_php'        => $interNetXSnippet,
            'registrar_module_php' => $registrarModuleSnippet,
        ];
    }

}

if (! function_exists('spAutoDnsPricelist_getSavedMinMargin')) {

    function spAutoDnsPricelist_getSavedMinMargin() : float
    {

        try {
            $value = Capsule::table('tbladdonmodules')
                ->where('module', 'spAutoDnsPricelist')
                ->where('setting', 'min_margin_percent')
                ->value('value');
            if ($value !== null && is_numeric((string) $value)) {
                return (float) $value;
            }
        } catch (\Throwable $e) {
            logActivity('spAutoDnsPricelist failed reading min_margin_percent: ' . $e->getMessage());
        }

        return 0.0;
    }

}

if (! function_exists('spAutoDnsPricelist_saveMinMargin')) {

    function spAutoDnsPricelist_saveMinMargin(float $margin) : bool
    {

        try {
            $formatted = number_format($margin, 2, '.', '');
            $exists    = Capsule::table('tbladdonmodules')
                ->where('module', 'spAutoDnsPricelist')
                ->where('setting', 'min_margin_percent')
                ->exists();

            if ($exists) {
                Capsule::table('tbladdonmodules')
                    ->where('module', 'spAutoDnsPricelist')
                    ->where('setting', 'min_margin_percent')
                    ->update(['value' => $formatted]);
            } else {
                Capsule::table('tbladdonmodules')->insert([
                    'module'  => 'spAutoDnsPricelist',
                    'setting' => 'min_margin_percent',
                    'value'   => $formatted,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            logActivity('spAutoDnsPricelist failed saving min_margin_percent: ' . $e->getMessage());
            return false;
        }
    }

}

if (! function_exists('spAutoDnsPricelist_getWhmcsTldPrices')) {

    function spAutoDnsPricelist_getWhmcsTldPrices() : array
    {

        try {
            $currencyId = (int) Capsule::table('tblcurrencies')->where('code', 'EUR')
                ->value('id');
            if ($currencyId <= 0) {
                $currencyId = (int) Capsule::table('tblcurrencies')->where('default', 1)
                    ->value('id');
            }
            if ($currencyId <= 0) {
                $currencyId = 1;
            }

            $apiResult = localAPI('GetTLDPricing', ['currencyid' => $currencyId]);
            if (! is_array($apiResult) || ($apiResult['result'] ?? '') !== 'success') {
                logActivity('spAutoDnsPricelist GetTLDPricing returned error.');
                return [];
            }

            $pricing = $apiResult['pricing'] ?? [];
            if (! is_array($pricing)) {
                return [];
            }

            $normalized = [];
            foreach ($pricing as $tldKey => $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $normTld = ltrim(strtolower((string) $tldKey), '.');
                if ($normTld === '') {
                    continue;
                }
                $normalized[$normTld] = [
                    'register' => is_array($entry['register'] ?? null) ? $entry['register'] : [],
                    'renew'    => is_array($entry['renew'] ?? null) ? $entry['renew'] : [],
                    'transfer' => is_array($entry['transfer'] ?? null) ? $entry['transfer'] : [],
                ];
            }

            return $normalized;
        } catch (\Throwable $e) {
            logActivity('spAutoDnsPricelist GetTLDPricing failed: ' . $e->getMessage());
            return [];
        }
    }

}

if (! function_exists('spAutoDnsPricelist_pickWhmcsPrice')) {

    function spAutoDnsPricelist_pickWhmcsPrice(array $pricesByPeriod, int $years) : ?float
    {

        $yearKey = (string) ($years > 0 ? $years : 1);
        if (isset($pricesByPeriod[$yearKey]) && is_numeric((string) $pricesByPeriod[$yearKey])) {
            $val = (float) $pricesByPeriod[$yearKey];
            if ($val >= 0.0) {
                return $val;
            }
        }
        if (isset($pricesByPeriod['1']) && is_numeric((string) $pricesByPeriod['1'])) {
            $val = (float) $pricesByPeriod['1'];
            if ($val >= 0.0) {
                return $val;
            }
        }

        return null;
    }

}

if (! function_exists('spAutoDnsPricelist_output')) {

    function spAutoDnsPricelist_output(array $vars) : void
    {

        $l            = spAutoDnsPricelist_lang();
        $modulelink   = (string) ($vars['modulelink'] ?? '');
        $allowedTabs  = ['price_list', 'tld_sync', 'health_check', 'help'];
        $requestedTab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'price_list';
        $tab          = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'price_list';
        $action       = is_string($_GET['action'] ?? null) ? $_GET['action'] : '';

        $username = (string) ($vars['username'] ?? '');
        $password = (string) ($vars['password'] ?? '');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || ! is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        if ($tab === 'health_check' && $action === 'repair' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
            if ($submittedToken !== '' && hash_equals($csrfToken, $submittedToken)) {
                echo '<div class="successbox"><strong>' . htmlspecialchars($l['HEALTH_REPAIR_SUCCESS'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
            } else {
                echo '<div class="errorbox"><strong>' . htmlspecialchars($l['ERROR_CSRF'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
            }
        }

        if ($tab === 'price_list' && $action === 'save_margin' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
            if ($submittedToken !== '' && hash_equals($csrfToken, $submittedToken)) {
                $rawMargin = str_replace(',', '.', trim((string) ($_POST['min_margin_percent'] ?? '0')));
                $newMargin = is_numeric($rawMargin) ? (float) $rawMargin : 0.0;
                $newMargin = max(-100.0, min(1000.0, round($newMargin, 2)));
                if (spAutoDnsPricelist_saveMinMargin($newMargin)) {
                    echo '<div class="successbox"><strong>' . htmlspecialchars($l['MARGIN_SAVED'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
                }
            } else {
                echo '<div class="errorbox"><strong>' . htmlspecialchars($l['ERROR_CSRF'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
            }
        }

        $savedMinMargin = spAutoDnsPricelist_getSavedMinMargin();

        $renderer = new SpAutoDnsPricelistRenderer($l, $modulelink);
        $renderer->renderHeader($tab);

        if ($tab === 'help') {
            $renderer->renderHelp();
            $renderer->renderFooter();
            return;
        }

        if ($tab === 'tld_sync') {
            $checks   = spAutoDnsPricelist_runTldSyncCheck($l);
            $snippets = spAutoDnsPricelist_getTldSyncSnippets();
            $renderer->renderTldSync($checks, $snippets);
            $renderer->renderFooter();
            return;
        }

        if ($tab === 'health_check') {
            $checks = spAutoDnsPricelist_runHealthCheck($vars);
            $renderer->renderHealthCheck($csrfToken, $checks);
            $renderer->renderFooter();
            return;
        }

        if ($username === '' || $password === '') {
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_CREDENTIALS_MISSING'], ENT_QUOTES, 'UTF-8') . '</div>';
            $renderer->renderFooter();
            return;
        }

        if (! extension_loaded('curl')) {
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_CURL'], ENT_QUOTES, 'UTF-8') . '</div>';
            $renderer->renderFooter();
            return;
        }

        $url = 'https://api.autodns.com/v1/document/price_list.xml';
        $ch  = curl_init();
        if ($ch === false) {
            logActivity('spAutoDnsPricelist cURL initialization failed.');
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_CURL'], ENT_QUOTES, 'UTF-8') . '</div>';
            $renderer->renderFooter();
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
            CURLOPT_USERPWD         => $username . ':' . $password,
            CURLOPT_HTTPHEADER      => [
                'Accept: application/xml',
            ],
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_SSLVERSION      => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 30,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch) !== 0 || $response === false) {
            $curlErr = curl_error($ch);
            curl_close($ch);
            logActivity('spAutoDnsPricelist cURL error: ' . $curlErr);
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_CURL'], ENT_QUOTES, 'UTF-8') . '</div>';
            $renderer->renderFooter();
            return;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300 || strpos((string) $response, '<?xml') === false) {
            logActivity('spAutoDnsPricelist HTTP error: status ' . $httpCode);
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_HTTP'], ENT_QUOTES, 'UTF-8') . ' ' . $httpCode . '</div>';
            $renderer->renderFooter();
            return;
        }

        $prevUseErrors = libxml_use_internal_errors(true);
        $xml           = simplexml_load_string((string) $response, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        if ($xml === false) {
            logActivity('spAutoDnsPricelist XML parse error for HTTP status ' . $httpCode);
            echo '<div class="errorbox">' . htmlspecialchars($l['ERROR_XML_PARSE'], ENT_QUOTES, 'UTF-8') . '</div>';
            $renderer->renderFooter();
            return;
        }

        $categories     = ['single' => [], 'double' => [], 'invalid' => []];
        $allowedCases   = ['create', 'renew', 'transfer', 'restore'];
        $whmcsTldPrices = spAutoDnsPricelist_getWhmcsTldPrices();

        if (isset($xml->prices->domain)) {
            foreach ($xml->prices->domain as $domain) {
                $domainLabel = (string) $domain['label'];
                $prices      = ['create' => '-', 'renew' => '-', 'transfer' => '-', 'restore' => '-'];
                $currency    = '-';
                $period      = '-';
                foreach ($domain->businessCase as $case) {
                    $caseLabel = (string) $case['label'];
                    if (! in_array($caseLabel, $allowedCases, true) || ! isset($case->price)) {
                        continue;
                    }
                    $price              = $case->price;
                    $prices[$caseLabel] = (string) $price['amount'];
                    $currency           = (string) $price['currency'];
                    $period             = (string) $price['period'];
                }

                $normTld       = ltrim(strtolower($domainLabel), '.');
                $periodYears   = (int) preg_replace('/[^0-9]/', '', $period);
                $periodYears   = $periodYears > 0 ? $periodYears : 1;
                $whmcsEntry    = $whmcsTldPrices[$normTld] ?? null;
                $whmcsRowPrice = null;
                if (is_array($whmcsEntry)) {
                    $whmcsRowPrice = [
                        'create'   => spAutoDnsPricelist_pickWhmcsPrice($whmcsEntry['register'], $periodYears),
                        'renew'    => spAutoDnsPricelist_pickWhmcsPrice($whmcsEntry['renew'], $periodYears),
                        'transfer' => spAutoDnsPricelist_pickWhmcsPrice($whmcsEntry['transfer'], $periodYears),
                    ];
                }

                $rowData = [$domainLabel, $prices, $currency, $period, $whmcsRowPrice];

                if (preg_match('/^xn--/', $domainLabel) || ! preg_match('/^[a-z0-9.-]+$/i', $domainLabel)) {
                    $categories['invalid'][] = $rowData;
                } else {
                    $labelParts = explode('.', $domainLabel);
                    $depth      = count($labelParts);
                    if ($depth === 1) {
                        $categories['single'][] = $rowData;
                    } else {
                        $categories['double'][] = $rowData;
                    }
                }
            }
        }

        $renderer->renderPriceList(
            $categories,
            $csrfToken,
            $savedMinMargin,
            $l['MARGIN_PLACEHOLDER'],
            $l['NO_ROUNDING'],
            $l['ROUND_WHOLE'],
            $l['ROUND_TENS'],
            $l['SEARCH_PLACEHOLDER'],
            $l['TAB_1LEVEL'],
            $l['TAB_2LEVEL'],
            $l['TAB_INVALID'],
            $l['COL_DOMAIN'],
            $l['COL_CREATE'],
            $l['COL_RENEW'],
            $l['COL_TRANSFER'],
            $l['COL_RESTORE'],
            $l['COL_CURRENCY'],
            $l['COL_PERIOD']
        );

        $renderer->renderFooter();
    }

}
