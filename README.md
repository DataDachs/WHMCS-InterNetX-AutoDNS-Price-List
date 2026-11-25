# AutoDNS Price List Module for WHMCS

This repository contains the **AutoDNS Price List** WHMCS addon module (`spAutoDnsPricelist`), which fetches and displays the InterNetX AutoDNS XML price list in the WHMCS admin area, along with the `InterNetX_GetTldPricing` integration for WHMCS **Registrar TLD Sync**.

It allows administrators to inspect domain pricing across TLDs and simulate margins and rounding adjustments before importing prices into WHMCS.

## Features
* Fetches the AutoDNS XML price list (`https://api.autodns.com/v1/document/price_list.xml`) over verified TLS 1.2+ HTTPS using HTTP Basic Authentication.
* Separates domains into **1-Level**, **2-Level**, and **Invalid/IDN Domains** tabs.
* Sortable domain table and live search filter for domain extensions.
* Built-in margin percentage calculation and rounding simulation (`No rounding`, `Round to whole numbers`, `Round to tens`).
* Compares live AutoDNS purchase prices against your currently configured WHMCS TLD prices (`GetTLDPricing`) using a saved **Fixed Min. Margin (%)**, highlights loss-making TLDs directly in red, and provides quick filters for configured and loss-making TLDs.
* Built-in **TLD Sync** tab that checks your local InterNetX registrar integration (presence of `InterNetX_GetTldPricing()`, strict TLS verification, and XML credential fields) and provides copyable setup snippets.
* Built-in **Health Check** tab (verifies PHP version, PHP cURL extension, and configured AutoDNS credentials) and **Help** tab.
* Supports 15 admin/module languages out of the box.
* **For viewing purposes only** (use **WHMCS > Utilities > Registrar TLD Sync** for automated TLD imports).

## Requirements
* WHMCS 8.x (tested with WHMCS 8.13)
* PHP 8.2 or later
* PHP `curl` and `simplexml` / `libxml` extensions enabled
* InterNetX AutoDNS account with the **XML Price List** service enabled

## Setup Instructions

1. **Activate the XML Price List in AutoDNS**
   * Log in to your AutoDNS account.
   * Navigate to **Services > Products** and select **APIs & Services**.
   * Enable the **XML Price List** service.
   * Add the IP address(es) of your WHMCS server that are allowed to access the XML price list.
   * Confirm by clicking **OK**.
   * **Note:** The XML price list is regenerated daily at 22:00 (Europe/Berlin time). It may take up to 24 hours after initial activation before the feed becomes available.

2. **Install the WHMCS Addon Module**
   * Clone or download this repository.
   * Upload `modules/addons/spAutoDnsPricelist/` to your WHMCS `/modules/addons/spAutoDnsPricelist/` directory.
   * In the WHMCS admin area, go to **System Settings > Addon Modules** (or **Setup > Addon Modules**).
   * Find **DD: AutoDNS Price List** and click **Activate**.

3. **Configure the Module**
   * Click **Configure** next to the addon, enter the AutoDNS XML **Username** and **Password**, and grant access to the desired admin roles.
   * Save the configuration.

## Usage
1. Go to **Addons > AutoDNS Price List** in the WHMCS admin area.
2. Browse the **Price List** tab across **1-Level**, **2-Level**, and **Invalid/IDN Domains**.
3. Enter a margin percentage and choose a rounding mode to preview customer pricing.
4. Use the **Health Check** tab to verify PHP, cURL, and credential configuration, and the **Help** tab for built-in documentation.
5. To import domains and pricing directly into WHMCS, navigate to **Utilities > Registrar TLD Sync**.

## Configuration Fields
* **Username**: AutoDNS username for the XML price list.
* **Password**: AutoDNS password for the XML price list.
* **Drop Tables on Deactivation**: Standard cleanup toggle upon module deactivation (no custom database tables are created by this addon).

## InterNetX Registrar TLD Sync Integration
This repository contains **only** the `spAutoDnsPricelist` addon module and does **not** ship any proprietary InterNetX registrar files.

1. Download and install the official InterNetX WHMCS Domain Plugin from [https://www.internetx.com/whmcs/](https://www.internetx.com/whmcs/).
2. Open the **TLD Sync** tab inside **Addons > AutoDNS Price List** in WHMCS.
3. The module checks automatically whether `InterNetX_GetTldPricing()`, strict TLS verification, and the XML credential fields are already present, and provides the two copyable PHP snippets to integrate into your local `modules/registrars/InterNetX/` files.

## Example

### Initial Table View

| Domain       | Create | Renew | Transfer | Restore | Currency | Period |
|--------------|--------|-------|----------|---------|----------|--------|
| example.com  | 10.00  | 10.00 | 10.00    | 70.00   | EUR      | 1 year |
| example.net  | 12.00  | 12.00 | 12.00    | 80.00   | EUR      | 1 year |

### Applying Margin and Rounding
1. Enter a margin percentage (e.g., `20`) in the margin input field.
2. Select a rounding option (`No rounding`, `Round to whole numbers`, or `Round to tens`).
3. The table updates all displayed prices dynamically.

## License
Licensed under the **DataDachs WHMCS Module License** (`Free & Commercial Edition`). See [LICENSE.md](LICENSE.md) for the full binding terms.

| Action | Allowed |
| :--- | :--- |
| Use in your own WHMCS (commercial hosting & personal) | Yes (free) |
| Customize code or templates for your own WHMCS | Yes |
| Bypass or remove license checks (where applicable) | No |
| Resell, redistribute, publish copies, or build competing products | No |

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
