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

require_once __DIR__ . '/Views/DDADStylesTrait.php';

class SpAutoDnsPricelistRenderer
{

    use DDADStylesTrait;

    private array $lang;

    private string $moduleLink;

    public function __construct(array $lang, string $moduleLink)
    {

        $this->lang       = $lang;
        $this->moduleLink = $moduleLink;
    }

    private function l(string $key) : string
    {

        return (string) $this->lang[$key];
    }

    public function renderHeader(string $activeTab) : void
    {

        $this->renderStyles();

        $safeModuleLink = htmlspecialchars($this->moduleLink, ENT_QUOTES, 'UTF-8');

        echo '<div class="ddad-wrapper">';
        echo '<div class="ddad-header">';
        echo '<h1 class="ddad-title"><i class="fas fa-file-invoice-dollar"></i> ' . htmlspecialchars($this->l('TITLE'), ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '</div>';

        echo '<div class="ddad-tabs">';
        echo '<a href="' . $safeModuleLink . '&tab=price_list" class="ddad-tab-btn' . ($activeTab === 'price_list' ? ' active' : '') . '"><i class="fas fa-list"></i> ' . htmlspecialchars($this->l('TAB_PRICE_LIST'), ENT_QUOTES, 'UTF-8') . '</a>';
        echo '<a href="' . $safeModuleLink . '&tab=tld_sync" class="ddad-tab-btn' . ($activeTab === 'tld_sync' ? ' active' : '') . '"><i class="fas fa-sync-alt"></i> ' . htmlspecialchars($this->l('TAB_TLD_SYNC'), ENT_QUOTES, 'UTF-8') . '</a>';
        echo '<a href="' . $safeModuleLink . '&tab=health_check" class="ddad-tab-btn' . ($activeTab === 'health_check' ? ' active' : '') . '"><i class="fas fa-heartbeat"></i> ' . htmlspecialchars($this->l('TAB_HEALTH'), ENT_QUOTES, 'UTF-8') . '</a>';
        echo '<a href="' . $safeModuleLink . '&tab=help" class="ddad-tab-btn' . ($activeTab === 'help' ? ' active' : '') . '"><i class="fas fa-question-circle"></i> ' . htmlspecialchars($this->l('TAB_HELP'), ENT_QUOTES, 'UTF-8') . '</a>';
        echo '</div>';
    }

    public function renderPriceList(
        array $categories,
        string $csrfToken,
        float $savedMinMargin,
        string $marginPlaceholder,
        string $noRounding,
        string $roundWhole,
        string $roundTens,
        string $searchPlaceholder,
        string $tab1Level,
        string $tab2Level,
        string $tabInvalid,
        string $colDomain,
        string $colCreate,
        string $colRenew,
        string $colTransfer,
        string $colRestore,
        string $colCurrency,
        string $colPeriod
    ) : void {

        $safeModuleLink = htmlspecialchars($this->moduleLink, ENT_QUOTES, 'UTF-8');
        $safeCsrfToken  = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');
        $marginValueStr = number_format($savedMinMargin, 2, '.', '');
        $marginDisplay  = rtrim(rtrim($marginValueStr, '0'), '.');
        if ($marginDisplay === '') {
            $marginDisplay = '0';
        }

        $configuredCount = 0;
        $lossCount       = 0;
        $multiplier      = 1.0 + ($savedMinMargin / 100.0);

        foreach ($categories as $domains) {
            foreach ($domains as $domainData) {
                $whmcsRowPrice = $domainData[4] ?? null;
                if (! is_array($whmcsRowPrice)) {
                    continue;
                }
                $configuredCount++;
                $prices  = $domainData[1];
                $rowLoss = false;
                foreach (['create', 'renew', 'transfer'] as $op) {
                    $whmcsVal = $whmcsRowPrice[$op] ?? null;
                    $ekRaw    = $prices[$op] ?? '-';
                    if ($whmcsVal !== null && is_numeric((string) $ekRaw)) {
                        if (((float) $whmcsVal + 0.0001) < ((float) $ekRaw * $multiplier)) {
                            $rowLoss = true;
                            break;
                        }
                    }
                }
                if ($rowLoss) {
                    $lossCount++;
                }
            }
        }

        echo '<div class="ddad-card">';
        echo '<p>' . $this->l('PAGE_DESC') . '</p>';

        if ($configuredCount === 0) {
            echo '<div id="ddadLossBanner" class="ddad-loss-banner ddad-loss-banner-neutral"><span><i class="fas fa-info-circle" style="margin-right:6px;"></i> ' . htmlspecialchars($this->l('LOSS_SUMMARY_NO_CONFIGURED'), ENT_QUOTES, 'UTF-8') . '</span></div>';
        } elseif ($lossCount > 0) {
            $bannerText = str_replace(['{count}', '{margin}'], [(string) $lossCount, $marginDisplay], $this->l('LOSS_SUMMARY_FOUND'));
            echo '<div id="ddadLossBanner" class="ddad-loss-banner ddad-loss-banner-danger"><span><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i> <span id="ddadLossBannerText">' . htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8') . '</span></span></div>';
        } else {
            $bannerText = str_replace(['{count}', '{margin}'], [(string) $configuredCount, $marginDisplay], $this->l('LOSS_SUMMARY_NONE'));
            echo '<div id="ddadLossBanner" class="ddad-loss-banner ddad-loss-banner-success"><span><i class="fas fa-check-circle" style="margin-right:6px;"></i> <span id="ddadLossBannerText">' . htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8') . '</span></span></div>';
        }

        echo '<form method="post" action="' . $safeModuleLink . '&tab=price_list&action=save_margin" class="row" style="margin-bottom: 16px; display:flex; align-items:flex-end; flex-wrap:wrap; row-gap:12px;">';
        echo '<input type="hidden" name="csrf_token" value="' . $safeCsrfToken . '">';
        echo '<div class="col-md-3">';
        echo '<label for="minMarginInput" style="font-size:12px; font-weight:600; color:#475569; margin-bottom:4px; display:block;">' . htmlspecialchars($this->l('MIN_MARGIN_LABEL'), ENT_QUOTES, 'UTF-8') . '</label>';
        echo '<input type="number" step="0.01" id="minMarginInput" name="min_margin_percent" class="ddad-form-control" value="' . htmlspecialchars($marginDisplay, ENT_QUOTES, 'UTF-8') . '" placeholder="' . htmlspecialchars($this->l('MIN_MARGIN_PLACEHOLDER'), ENT_QUOTES, 'UTF-8') . '" oninput="updateLossHighlights()">';
        echo '</div>';
        echo '<div class="col-md-2">';
        echo '<button type="submit" class="ddad-btn ddad-btn-primary" style="width:100%;"><i class="fas fa-save"></i> ' . htmlspecialchars($this->l('BTN_SAVE_MARGIN'), ENT_QUOTES, 'UTF-8') . '</button>';
        echo '</div>';
        echo '<div class="col-md-3"><input type="number" id="marginInput" class="ddad-form-control" placeholder="' . htmlspecialchars($marginPlaceholder, ENT_QUOTES, 'UTF-8') . '" oninput="applyMarginAndRound()"></div>';
        echo '<div class="col-md-4">';
        echo '<select id="roundingOption" class="ddad-form-control" onchange="applyMarginAndRound()">';
        echo '<option value="none">' . htmlspecialchars($noRounding, ENT_QUOTES, 'UTF-8') . '</option>';
        echo '<option value="1">' . htmlspecialchars($roundWhole, ENT_QUOTES, 'UTF-8') . '</option>';
        echo '<option value="10">' . htmlspecialchars($roundTens, ENT_QUOTES, 'UTF-8') . '</option>';
        echo '</select></div>';
        echo '</form>';

        echo '<div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom: 16px;">';
        echo '<label style="font-size:13px; font-weight:600; color:#334155; cursor:pointer; margin:0; display:inline-flex; align-items:center; gap:6px;"><input type="checkbox" id="lossOnlyToggle" onchange="filterDomains()"> ' . htmlspecialchars($this->l('FILTER_LOSS_ONLY'), ENT_QUOTES, 'UTF-8') . '</label>';
        echo '<label style="font-size:13px; font-weight:600; color:#334155; cursor:pointer; margin:0; display:inline-flex; align-items:center; gap:6px;"><input type="checkbox" id="configuredOnlyToggle" onchange="filterDomains()"> ' . htmlspecialchars($this->l('FILTER_CONFIGURED_ONLY'), ENT_QUOTES, 'UTF-8') . '</label>';
        echo '</div>';

        echo '<input type="text" id="domainSearch" class="ddad-form-control" placeholder="' . htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') . '" onkeyup="filterDomains()" style="margin-bottom: 20px;">';

        echo '<ul class="nav nav-tabs admin-tabs">';
        echo '<li class="active"><a href="#single" data-toggle="tab">' . htmlspecialchars($tab1Level, ENT_QUOTES, 'UTF-8') . '</a></li>';
        echo '<li><a href="#double" data-toggle="tab">' . htmlspecialchars($tab2Level, ENT_QUOTES, 'UTF-8') . '</a></li>';
        echo '<li><a href="#invalid" data-toggle="tab">' . htmlspecialchars($tabInvalid, ENT_QUOTES, 'UTF-8') . '</a></li>';
        echo '</ul>';

        echo '<div class="tab-content admin-tabs-content" style="padding-top:15px;">';
        $whmcsPrefix     = htmlspecialchars($this->l('WHMCS_PRICE_PREFIX'), ENT_QUOTES, 'UTF-8');
        $badgeConfigured = htmlspecialchars($this->l('BADGE_CONFIGURED'), ENT_QUOTES, 'UTF-8');
        $badgeLoss       = htmlspecialchars($this->l('BADGE_LOSS'), ENT_QUOTES, 'UTF-8');

        foreach ($categories as $category => $domains) {
            $activeClass  = ($category === 'single') ? 'active' : '';
            $safeCategory = htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8');
            echo '<div id="' . $safeCategory . '" class="tab-pane ' . $activeClass . '">';
            echo '<table class="ddad-table domain-table" style="margin-top: 0;">';
            echo '<thead><tr>';
            echo '<th onclick="sortTable(this, 0)" style="cursor:pointer;">' . htmlspecialchars($colDomain, ENT_QUOTES, 'UTF-8') . ' <span style="font-size:10px;">&#9650;&#9660;</span></th>';
            echo '<th>' . htmlspecialchars($colCreate, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '<th>' . htmlspecialchars($colRenew, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '<th>' . htmlspecialchars($colTransfer, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '<th>' . htmlspecialchars($colRestore, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '<th>' . htmlspecialchars($colCurrency, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '<th>' . htmlspecialchars($colPeriod, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($domains as $domainData) {
                $domainLabel   = (string) $domainData[0];
                $prices        = $domainData[1];
                $currency      = (string) $domainData[2];
                $period        = (string) $domainData[3];
                $whmcsRowPrice = $domainData[4] ?? null;
                $isConfigured  = is_array($whmcsRowPrice);

                $cellLoss = ['create' => false, 'renew' => false, 'transfer' => false];
                $rowLoss  = false;
                if ($isConfigured) {
                    foreach (['create', 'renew', 'transfer'] as $op) {
                        $whmcsVal = $whmcsRowPrice[$op] ?? null;
                        $ekRaw    = $prices[$op] ?? '-';
                        if ($whmcsVal !== null && is_numeric((string) $ekRaw)) {
                            if (((float) $whmcsVal + 0.0001) < ((float) $ekRaw * $multiplier)) {
                                $cellLoss[$op] = true;
                                $rowLoss       = true;
                            }
                        }
                    }
                }

                $rowClasses = [];
                if ($rowLoss) {
                    $rowClasses[] = 'ddad-row-loss';
                }
                echo '<tr class="' . implode(' ', $rowClasses) . '" data-domain="' . htmlspecialchars($domainLabel, ENT_QUOTES, 'UTF-8') . '" data-configured="' . ($isConfigured ? '1' : '0') . '" data-loss="' . ($rowLoss ? '1' : '0') . '">';
                echo '<td><span class="ddad-domain-name">' . htmlspecialchars($domainLabel, ENT_QUOTES, 'UTF-8') . '</span>';
                if ($isConfigured) {
                    echo '<span class="ddad-tld-badges">';
                    echo '<span class="ddad-badge ddad-badge-success">' . $badgeConfigured . '</span>';
                    echo '<span class="ddad-badge ddad-badge-danger ddad-loss-badge" style="' . ($rowLoss ? '' : 'display:none;') . '">' . $badgeLoss . '</span>';
                    echo '</span>';
                }
                echo '</td>';

                foreach (['create', 'renew', 'transfer'] as $op) {
                    $ekStr     = (string) ($prices[$op] ?? '-');
                    $whmcsVal  = $isConfigured ? ($whmcsRowPrice[$op] ?? null) : null;
                    $tdClass   = $cellLoss[$op] ? ' class="ddad-cell-loss"' : '';
                    $whmcsAttr = $whmcsVal !== null ? ' data-whmcs-price="' . htmlspecialchars(number_format((float) $whmcsVal, 2, '.', ''), ENT_QUOTES, 'UTF-8') . '"' : '';
                    echo '<td' . $tdClass . ' data-ek-price="' . htmlspecialchars($ekStr, ENT_QUOTES, 'UTF-8') . '"' . $whmcsAttr . '>';
                    echo '<span class="ddad-ek-val">' . htmlspecialchars($ekStr, ENT_QUOTES, 'UTF-8') . '</span>';
                    if ($whmcsVal !== null) {
                        echo '<span class="ddad-whmcs-sub">' . $whmcsPrefix . ' ' . htmlspecialchars(number_format((float) $whmcsVal, 2, '.', ''), ENT_QUOTES, 'UTF-8') . '</span>';
                    }
                    echo '</td>';
                }

                echo '<td data-ek-price="' . htmlspecialchars((string) $prices['restore'], ENT_QUOTES, 'UTF-8') . '"><span class="ddad-ek-val">' . htmlspecialchars((string) $prices['restore'], ENT_QUOTES, 'UTF-8') . '</span></td>';
                echo '<td>' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($period, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
            if (empty($domains)) {
                echo '<tr><td colspan="7" style="text-align:center;">' . htmlspecialchars($this->l('NO_RECORDS'), ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }
        echo '</div>';

        echo '<script>
            var ddadConfiguredTotal = ' . (int) $configuredCount . ';
            var ddadLossFoundTpl = ' . json_encode($this->l('LOSS_SUMMARY_FOUND'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
            var ddadLossNoneTpl = ' . json_encode($this->l('LOSS_SUMMARY_NONE'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';

            function filterDomains() {
                var input = document.getElementById("domainSearch");
                var filter = input ? input.value.toLowerCase() : "";
                var lossOnly = document.getElementById("lossOnlyToggle") && document.getElementById("lossOnlyToggle").checked;
                var configuredOnly = document.getElementById("configuredOnlyToggle") && document.getElementById("configuredOnlyToggle").checked;
                var tables = document.querySelectorAll(".domain-table tbody");
                tables.forEach(function(tbody) {
                    var rows = tbody.getElementsByTagName("tr");
                    for (var i = 0; i < rows.length; i++) {
                        var row = rows[i];
                        var domainName = row.getAttribute("data-domain");
                        if (domainName === null) {
                            continue;
                        }
                        var isConfigured = row.getAttribute("data-configured") === "1";
                        var isLoss = row.getAttribute("data-loss") === "1";
                        var matchesText = domainName.toLowerCase().indexOf(filter) > -1;
                        var matchesLoss = !lossOnly || isLoss;
                        var matchesConfigured = !configuredOnly || isConfigured;
                        row.style.display = (matchesText && matchesLoss && matchesConfigured) ? "" : "none";
                    }
                });
            }

            function updateLossHighlights() {
                var minMarginInput = document.getElementById("minMarginInput");
                var minMarginRaw = minMarginInput ? parseFloat(minMarginInput.value) : 0;
                var minMargin = isNaN(minMarginRaw) ? 0 : minMarginRaw;
                var multiplier = 1 + (minMargin / 100);
                var lossRows = 0;

                var rows = document.querySelectorAll(".domain-table tbody tr[data-configured=\'1\']");
                rows.forEach(function(row) {
                    var rowHasLoss = false;
                    var cells = row.querySelectorAll("td[data-whmcs-price]");
                    cells.forEach(function(cell) {
                        var ekPrice = parseFloat(cell.getAttribute("data-ek-price"));
                        var whmcsPrice = parseFloat(cell.getAttribute("data-whmcs-price"));
                        if (!isNaN(ekPrice) && !isNaN(whmcsPrice)) {
                            if ((whmcsPrice + 0.0001) < (ekPrice * multiplier)) {
                                cell.classList.add("ddad-cell-loss");
                                rowHasLoss = true;
                            } else {
                                cell.classList.remove("ddad-cell-loss");
                            }
                        }
                    });
                    var lossBadge = row.querySelector(".ddad-loss-badge");
                    if (rowHasLoss) {
                        row.classList.add("ddad-row-loss");
                        row.setAttribute("data-loss", "1");
                        if (lossBadge) lossBadge.style.display = "";
                        lossRows++;
                    } else {
                        row.classList.remove("ddad-row-loss");
                        row.setAttribute("data-loss", "0");
                        if (lossBadge) lossBadge.style.display = "none";
                    }
                });

                var banner = document.getElementById("ddadLossBanner");
                var bannerText = document.getElementById("ddadLossBannerText");
                if (banner && bannerText && ddadConfiguredTotal > 0) {
                    var marginText = String(minMargin);
                    if (lossRows > 0) {
                        banner.className = "ddad-loss-banner ddad-loss-banner-danger";
                        bannerText.textContent = ddadLossFoundTpl.replace("{count}", String(lossRows)).replace("{margin}", marginText);
                    } else {
                        banner.className = "ddad-loss-banner ddad-loss-banner-success";
                        bannerText.textContent = ddadLossNoneTpl.replace("{count}", String(ddadConfiguredTotal)).replace("{margin}", marginText);
                    }
                }
                filterDomains();
            }

            function applyMarginAndRound() {
                var margin = parseFloat(document.getElementById("marginInput").value) || 0;
                var rounding = document.getElementById("roundingOption").value;
                var tables = document.querySelectorAll(".domain-table tbody");
                tables.forEach(function(tbody) {
                    var rows = tbody.getElementsByTagName("tr");
                    for (var i = 0; i < rows.length; i++) {
                        var cells = rows[i].querySelectorAll("td[data-ek-price]");
                        cells.forEach(function(cell) {
                            var ekPrice = parseFloat(cell.getAttribute("data-ek-price"));
                            var ekSpan = cell.querySelector(".ddad-ek-val");
                            if (!isNaN(ekPrice) && ekSpan) {
                                var newPrice = ekPrice + (ekPrice * margin / 100);
                                if (rounding === "1") {
                                    newPrice = Math.ceil(newPrice);
                                } else if (rounding === "10") {
                                    newPrice = Math.ceil(newPrice / 10) * 10;
                                }
                                ekSpan.textContent = newPrice.toFixed(2);
                            }
                        });
                    }
                });
            }

            function sortTable(header, columnIndex) {
                var table = header.closest("table");
                var rows = Array.from(table.querySelectorAll("tbody tr"));
                var isAscending = !header.classList.contains("asc");

                table.querySelectorAll("th").forEach(function(th) {
                    th.classList.remove("asc", "desc");
                });

                header.classList.add(isAscending ? "asc" : "desc");

                rows.sort(function (a, b) {
                    var cellElA = a.getElementsByTagName("td")[columnIndex];
                    var cellElB = b.getElementsByTagName("td")[columnIndex];
                    var spanA = cellElA ? cellElA.querySelector(".ddad-domain-name") : null;
                    var spanB = cellElB ? cellElB.querySelector(".ddad-domain-name") : null;
                    var cellA = spanA ? spanA.textContent.trim() : (cellElA ? cellElA.textContent.trim() : "");
                    var cellB = spanB ? spanB.textContent.trim() : (cellElB ? cellElB.textContent.trim() : "");

                    var valueA = cellA.toLowerCase();
                    var valueB = cellB.toLowerCase();

                    if (valueA < valueB) return isAscending ? -1 : 1;
                    if (valueA > valueB) return isAscending ? 1 : -1;
                    return 0;
                });

                var tbody = table.querySelector("tbody");
                rows.forEach(function (row) {
                    tbody.appendChild(row);
                });
            }
        </script>';
        echo '</div>';
    }

    public function renderHealthCheck(string $csrfToken, array $checks) : void
    {

        $failedCount = 0;
        $warnCount   = 0;
        foreach ($checks as $c) {
            if ($c['status'] === 'fail') {
                $failedCount++;
            } elseif ($c['status'] === 'warn') {
                $warnCount++;
            }
        }

        $safeModuleLink = htmlspecialchars($this->moduleLink, ENT_QUOTES, 'UTF-8');
        $safeCsrfToken  = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');

        echo '<div class="ddad-health-summary">';
        echo '<div class="ddad-health-summary-info">';
        echo '<div class="ddad-health-summary-title">' . htmlspecialchars($this->l('HEALTH_SUMMARY_TITLE'), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div style="font-size: 14px; color:#475569;">';
        if ($failedCount > 0) {
            echo '<span style="color:#ef4444; font-weight:600;">' . $failedCount . ' ' . htmlspecialchars($this->l('HEALTH_ERRORS'), ENT_QUOTES, 'UTF-8') . '</span> ' . htmlspecialchars($this->l('HEALTH_ACTION_REQUIRED'), ENT_QUOTES, 'UTF-8');
        } elseif ($warnCount > 0) {
            echo '<span style="color:#f59e0b; font-weight:600;">' . $warnCount . ' ' . htmlspecialchars($this->l('HEALTH_WARNINGS'), ENT_QUOTES, 'UTF-8') . '</span> ' . htmlspecialchars($this->l('HEALTH_SOME_WARNINGS'), ENT_QUOTES, 'UTF-8');
        } else {
            echo '<span style="color:#10b981; font-weight:600;">' . htmlspecialchars($this->l('HEALTH_ALL_OK'), ENT_QUOTES, 'UTF-8') . '</span> ' . htmlspecialchars($this->l('HEALTH_SYSTEM_HEALTHY'), ENT_QUOTES, 'UTF-8');
        }
        echo '</div>';
        echo '</div>';

        echo '<form method="post" action="' . $safeModuleLink . '&tab=health_check&action=repair">';
        echo '<input type="hidden" name="csrf_token" value="' . $safeCsrfToken . '">';
        echo '<button type="submit" class="ddad-btn ddad-btn-primary"><i class="fas fa-tools"></i> ' . htmlspecialchars($this->l('HEALTH_BTN_REPAIR'), ENT_QUOTES, 'UTF-8') . '</button>';
        echo '</form>';
        echo '</div>';

        echo '<div class="ddad-health-grid">';
        foreach ($checks as $c) {
            $statusHtml = '';
            if ($c['status'] === 'pass') {
                $statusHtml = '<span class="ddad-badge ddad-badge-success"><i class="fas fa-check-circle" style="margin-right:4px;"></i> Pass</span>';
            } elseif ($c['status'] === 'warn') {
                $statusHtml = '<span class="ddad-badge ddad-badge-warning"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> Warning</span>';
            } else {
                $statusHtml = '<span class="ddad-badge ddad-badge-danger"><i class="fas fa-times-circle" style="margin-right:4px;"></i> Fail</span>';
            }

            echo '<div class="ddad-health-card">';
            echo '<div class="ddad-health-info">';
            echo '<span class="ddad-health-title">' . htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '<span class="ddad-health-desc">' . htmlspecialchars((string) $c['desc'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
            echo '<div class="ddad-health-status">' . $statusHtml . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function renderTldSync(array $checks, array $snippets) : void
    {

        echo '<div class="ddad-card">';
        echo '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">';
        echo '<h3 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">' . htmlspecialchars($this->l('TLD_SYNC_TITLE'), ENT_QUOTES, 'UTF-8') . '</h3>';
        echo '<a href="https://www.internetx.com/whmcs/" target="_blank" rel="noopener" class="ddad-btn ddad-btn-primary"><i class="fas fa-external-link-alt"></i> ' . htmlspecialchars($this->l('TLD_SYNC_OFFICIAL_BTN'), ENT_QUOTES, 'UTF-8') . '</a>';
        echo '</div>';
        echo '<p style="font-size:14px; color:#475569; margin-bottom:0;">' . htmlspecialchars($this->l('TLD_SYNC_DESC'), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div>';

        echo '<h4 style="font-size:16px; font-weight:700; color:#0f172a; margin:0 0 12px 0;">' . htmlspecialchars($this->l('TLD_SYNC_STATUS_TITLE'), ENT_QUOTES, 'UTF-8') . '</h4>';
        echo '<div class="ddad-health-grid" style="margin-top:0; margin-bottom:24px;">';
        foreach ($checks as $c) {
            if ($c['status'] === 'pass') {
                $statusHtml = '<span class="ddad-badge ddad-badge-success"><i class="fas fa-check-circle" style="margin-right:4px;"></i> Pass</span>';
            } elseif ($c['status'] === 'warn') {
                $statusHtml = '<span class="ddad-badge ddad-badge-warning"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> Warning</span>';
            } else {
                $statusHtml = '<span class="ddad-badge ddad-badge-danger"><i class="fas fa-times-circle" style="margin-right:4px;"></i> Fail</span>';
            }

            echo '<div class="ddad-health-card">';
            echo '<div class="ddad-health-info">';
            echo '<span class="ddad-health-title">' . htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '<span class="ddad-health-desc">' . htmlspecialchars((string) $c['desc'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
            echo '<div class="ddad-health-status">' . $statusHtml . '</div>';
            echo '</div>';
        }
        echo '</div>';

        $blocks = [
            [
                'id'    => 'ddadSnippetInterNetX',
                'title' => $this->l('TLD_SYNC_SNIPPET_1_TITLE'),
                'desc'  => $this->l('TLD_SYNC_SNIPPET_1_DESC'),
                'code'  => (string) ($snippets['internetx_php'] ?? ''),
            ],
            [
                'id'    => 'ddadSnippetRegistrarModule',
                'title' => $this->l('TLD_SYNC_SNIPPET_2_TITLE'),
                'desc'  => $this->l('TLD_SYNC_SNIPPET_2_DESC'),
                'code'  => (string) ($snippets['registrar_module_php'] ?? ''),
            ],
        ];

        $copyLabel = htmlspecialchars($this->l('TLD_SYNC_BTN_COPY'), ENT_QUOTES, 'UTF-8');

        foreach ($blocks as $block) {
            $safeId = htmlspecialchars($block['id'], ENT_QUOTES, 'UTF-8');
            echo '<div class="ddad-card">';
            echo '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:8px;">';
            echo '<h4 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">' . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') . '</h4>';
            echo '<button type="button" class="ddad-btn ddad-btn-primary" onclick="ddadCopySnippet(\'' . $safeId . '\', this)"><i class="fas fa-copy"></i> <span>' . $copyLabel . '</span></button>';
            echo '</div>';
            echo '<p style="font-size:13px; color:#475569; margin-bottom:12px;">' . htmlspecialchars($block['desc'], ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<pre id="' . $safeId . '" style="background:#0f172a; color:#e2e8f0; padding:16px; border-radius:12px; overflow-x:auto; font-size:12px; line-height:1.5; margin:0;"><code>' . htmlspecialchars($block['code'], ENT_QUOTES, 'UTF-8') . '</code></pre>';
            echo '</div>';
        }

        echo '<script>
            function ddadCopySnippet(elementId, button) {
                var el = document.getElementById(elementId);
                if (!el) return;
                var text = el.innerText || el.textContent || "";
                var labelSpan = button.querySelector("span");
                var defaultText = ' . json_encode($this->l('TLD_SYNC_BTN_COPY'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
                var copiedText = ' . json_encode($this->l('TLD_SYNC_BTN_COPIED'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
                function markCopied() {
                    if (labelSpan) {
                        labelSpan.textContent = copiedText;
                        setTimeout(function() { labelSpan.textContent = defaultText; }, 1800);
                    }
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(markCopied).catch(function() {});
                    return;
                }
                var ta = document.createElement("textarea");
                ta.value = text;
                ta.style.position = "fixed";
                ta.style.opacity = "0";
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand("copy");
                    markCopied();
                } finally {
                    document.body.removeChild(ta);
                }
            }
        </script>';
    }

    public function renderFooter() : void
    {

        echo '</div>';
    }

    public function renderHelp() : void
    {

        $items = [
            ['HELP_WHAT', 'HELP_WHAT_TEXT'],
            ['HELP_CONFIG', 'HELP_CONFIG_TEXT'],
            ['HELP_HEALTH', 'HELP_HEALTH_TEXT'],
            ['HELP_SUPPORT', 'HELP_SUPPORT_TEXT'],
        ];
        echo '<h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 15px;">' . htmlspecialchars($this->l('HELP_TITLE'), ENT_QUOTES, 'UTF-8') . '</h3>';

        echo '<div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">';
        foreach ($items as [$qKey, $aKey]) {
            echo '<div class="ddad-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">';
            echo '<h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 10px;"><i class="fas fa-question-circle" style="color: #3b82f6; margin-right: 6px;"></i> ' . htmlspecialchars($this->l($qKey), ENT_QUOTES, 'UTF-8') . '</h4>';
            echo '<div style="font-size: 13px; color: #475569; line-height: 1.6;">' . nl2br(htmlspecialchars($this->l($aKey), ENT_QUOTES, 'UTF-8')) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="alert alert-warning" style="margin-top:0px; margin-bottom:20px; font-size:13px; line-height:1.5; border-left: 5px solid #f0ad4e; padding: 15px;">';
        echo '<strong><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($this->l('HELP_WARNING_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong>';
        echo '<div style="margin-top:5px;">' . htmlspecialchars($this->l('HELP_WARNING_TEXT'), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '</div>';
    }

}
