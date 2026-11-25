<?php

declare(strict_types=1);

/*
 * AutoDNS Price List Addon
 *
 * Fetches and displays the AutoDNS XML price list in the WHMCS admin area.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

trait DDADStylesTrait
{
    public function renderStyles(): void
    {
        echo '
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap");
            
            .ddad-wrapper {
                font-family: "Outfit", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background-color: #f8fafc;
                padding: 24px;
                border-radius: 20px;
                box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);
                margin: 15px 0;
            }
            .ddad-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 28px;
            }
            .ddad-title {
                font-size: 26px;
                font-weight: 700;
                color: #0f172a;
                margin: 0;
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .ddad-tabs {
                display: flex;
                gap: 8px;
                border-bottom: 2px solid #e2e8f0;
                margin-bottom: 28px;
                padding-bottom: 2px;
                flex-wrap: wrap;
            }
            .ddad-tab-btn {
                background: none;
                border: none;
                padding: 12px 20px;
                font-size: 15px;
                font-weight: 600;
                color: #64748b;
                border-radius: 12px 12px 0 0;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                position: relative;
            }
            .ddad-tab-btn:hover {
                color: #3b82f6;
                background-color: rgba(59, 130, 246, 0.04);
            }
            .ddad-tab-btn.active {
                color: #3b82f6;
            }
            .ddad-tab-btn.active::after {
                content: "";
                position: absolute;
                bottom: -4px;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                border-radius: 4px;
            }
            .ddad-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                margin-bottom: 24px;
            }
            .ddad-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin-top: 15px;
                background-color: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            .ddad-table th {
                background-color: #f8fafc;
                color: #475569;
                font-weight: 600;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                padding: 16px 20px;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
            }
            .ddad-table td {
                padding: 16px 20px;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
                font-size: 14px;
                vertical-align: middle;
            }
            .ddad-table tr:last-child td {
                border-bottom: none;
            }
            .ddad-table tr:hover td {
                background-color: #f8fafc;
            }
            .ddad-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 18px;
                font-size: 14px;
                font-weight: 600;
                border-radius: 10px;
                border: 1px solid transparent;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none !important;
                gap: 8px;
            }
            .ddad-btn-primary {
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                color: #ffffff !important;
            }
            .ddad-btn-primary:hover {
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
                opacity: 0.95;
            }
            .ddad-badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 9999px;
                text-transform: capitalize;
            }
            .ddad-badge-success {
                background-color: #ecfdf5;
                color: #065f46;
            }
            .ddad-badge-warning {
                background-color: #fffbeb;
                color: #92400e;
            }
            .ddad-badge-danger {
                background-color: #fef2f2;
                color: #991b1b;
            }
            
            /* Form formatting */
            .ddad-form-control {
                width: 100%;
                padding: 10px 16px;
                font-size: 14px;
                border-radius: 10px;
                border: 1px solid #cbd5e1;
                transition: all 0.2s;
                background-color: #ffffff;
            }
            .ddad-form-control:focus {
                border-color: #3b82f6;
                outline: none;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            /* Health check styles */
            .ddad-health-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .ddad-health-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }
            .ddad-health-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .ddad-health-title {
                font-weight: 600;
                color: #1e293b;
                font-size: 15px;
            }
            .ddad-health-desc {
                font-size: 13px;
                color: #64748b;
            }
            .ddad-health-summary {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                border: 1px solid #cbd5e1;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .ddad-health-summary-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .ddad-health-summary-title {
                font-size: 18px;
                font-weight: 700;
                color: #0f172a;
            }
            .ddad-table tr.ddad-row-loss td {
                background-color: #fef2f2;
                color: #991b1b;
            }
            .ddad-table tr.ddad-row-loss:hover td {
                background-color: #fee2e2;
            }
            .ddad-table td.ddad-cell-loss {
                font-weight: 700;
                color: #b91c1c;
            }
            .ddad-whmcs-sub {
                display: block;
                font-size: 11px;
                font-weight: 600;
                color: #64748b;
                margin-top: 2px;
            }
            .ddad-table td.ddad-cell-loss .ddad-whmcs-sub {
                color: #b91c1c;
            }
            .ddad-tld-badges {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-left: 8px;
                flex-wrap: wrap;
            }
            .ddad-loss-banner {
                border-radius: 12px;
                padding: 12px 16px;
                margin-bottom: 18px;
                font-size: 14px;
                font-weight: 600;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
            }
            .ddad-loss-banner-danger {
                background-color: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
            }
            .ddad-loss-banner-success {
                background-color: #ecfdf5;
                border: 1px solid #a7f3d0;
                color: #065f46;
            }
            .ddad-loss-banner-neutral {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #475569;
            }
        </style>
        ';
    }
}
