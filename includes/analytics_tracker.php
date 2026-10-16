<?php
/**
 * Analytics Tracker Script
 * Fort Education System - Website Traffic Monitoring
 *
 * This file is automatically included on all public pages to track visitor activity.
 * DO NOT include this file on admin pages (AdminCP folder).
 */

// Only track if not in admin panel
if (strpos($_SERVER['PHP_SELF'], 'AdminCP') === false) {

    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Include analytics helper functions
    if (!function_exists('trackPageView')) {
        require_once __DIR__ . '/analytics_helper.php';
    }

    // Include database connection
    if (!isset($conn)) {
        require_once __DIR__ . '/config.php';
    }

    // Track this page view
    trackPageView($conn);
}
?>
