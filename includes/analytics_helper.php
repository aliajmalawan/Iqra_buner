<?php
/**
 * Analytics Helper Functions
 * Fort Education System - Website Traffic Monitoring
 */

/**
 * This XAMPP instance hosts several separate client site projects side by side, and some of
 * their copies of this same helper file were never pointed at their own database — so this
 * table also collects page views from those other, unrelated sites. It also picks up hits from
 * command-line/automation tools (curl, headless browsers, etc.) that aren't real visitors.
 * Every analytics query below is scoped to exclude both, so the dashboard only reflects real
 * traffic to this site.
 */
// URL path this site is served from: "/" on the live server, "/Iqra_buner/" locally.
// Visits recorded under the project's previous local folder name are still counted.
function analyticsPathCondition($column) {
    global $conn;
    $base = parse_url(SITE_URL, PHP_URL_PATH) ?: '/';
    $base = rtrim($base, '/') . '/';
    $cond = "$column LIKE '" . mysqli_real_escape_string($conn, $base) . "%'";
    if ($base !== '/') $cond .= " OR $column LIKE '/bahawalcollegeofhealth/%'";
    return $cond;
}

function getAnalyticsScopeCondition() {
    return "(" . analyticsPathCondition('page_url') . ")
            AND (user_agent IS NULL OR (
                user_agent NOT LIKE '%curl%' AND user_agent NOT LIKE '%wget%' AND
                user_agent NOT LIKE '%python%' AND user_agent NOT LIKE '%HeadlessChrome%' AND
                user_agent NOT LIKE '%PhantomJS%' AND user_agent NOT LIKE '%Postman%' AND
                user_agent NOT LIKE '%bot%' AND user_agent NOT LIKE '%spider%' AND user_agent NOT LIKE '%crawl%'
            ))";
}

// Get visitor's IP address
function getVisitorIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Get or create session ID for visitor tracking
function getVisitorSessionID() {
    // Use PHP's native session ID which persists across page loads
    if (!isset($_SESSION['visitor_session_id']) || empty($_SESSION['visitor_session_id'])) {
        // Get current session ID, if empty create new one
        $current_session_id = session_id();
        if (empty($current_session_id)) {
            // Session not started properly, create unique ID
            $current_session_id = md5(uniqid(rand(), true) . getVisitorIP());
        }
        $_SESSION['visitor_session_id'] = $current_session_id;
    }
    return $_SESSION['visitor_session_id'];
}

// Detect device type from user agent
function getDeviceType($user_agent) {
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        return 'Tablet';
    }
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        return 'Mobile';
    }
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        return 'Tablet';
    }
    return 'Desktop';
}

// Detect browser from user agent
function getBrowser($user_agent) {
    $browsers = array(
        'Edge' => 'Edge',
        'Edg' => 'Edge',
        'OPR' => 'Opera',
        'Opera' => 'Opera',
        'Chrome' => 'Chrome',
        'Safari' => 'Safari',
        'Firefox' => 'Firefox',
        'MSIE' => 'Internet Explorer',
        'Trident' => 'Internet Explorer'
    );

    foreach ($browsers as $key => $value) {
        if (strpos($user_agent, $key) !== false) {
            return $value;
        }
    }
    return 'Unknown';
}

// Detect operating system from user agent
function getOperatingSystem($user_agent) {
    $os_array = array(
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            return $value;
        }
    }
    return 'Unknown OS';
}

// Track page view
function trackPageView($conn) {
    // Don't track admin pages
    if (strpos($_SERVER['PHP_SELF'], 'AdminCP') !== false) {
        return;
    }

    $visitor_ip = getVisitorIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Create unique fingerprint based on IP + User Agent hash (more stable than session)
    $visitor_fingerprint = md5($visitor_ip . substr($user_agent, 0, 200));

    // Use session ID if available, otherwise use fingerprint
    $session_id = getVisitorSessionID();
    if (empty($session_id)) {
        $session_id = $visitor_fingerprint;
    }

    $page_url = mysqli_real_escape_string($conn, $_SERVER['PHP_SELF']);
    $page_title = mysqli_real_escape_string($conn, isset($page_title_meta) ? $page_title_meta : '');
    $referrer = mysqli_real_escape_string($conn, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct');
    $device_type = getDeviceType($user_agent);
    $browser = getBrowser($user_agent);
    $os = getOperatingSystem($user_agent);
    $visit_date = date('Y-m-d');
    $visit_time = date('H:i:s');

    // Insert page view
    $sql = "INSERT INTO website_analytics
            (visitor_ip, page_url, page_title, referrer, user_agent, device_type, browser, operating_system, session_id, visit_date, visit_time)
            VALUES
            ('$visitor_ip', '$page_url', '$page_title', '$referrer', '$user_agent', '$device_type', '$browser', '$os', '$session_id', '$visit_date', '$visit_time')";

    mysqli_query($conn, $sql);

    // Update or insert unique visitor, keyed on session_id (matches the `unique_session` UNIQUE constraint).
    // Using INSERT ... ON DUPLICATE KEY UPDATE keeps this atomic — a SELECT-then-branch here
    // raced under concurrent requests and could try to set session_id to a value another
    // row already held, violating the unique key.
    mysqli_query($conn, "INSERT INTO unique_visitors (visitor_ip, session_id, user_agent, total_visits, last_visit)
                          VALUES ('$visitor_ip', '$session_id', '$user_agent', 1, NOW())
                          ON DUPLICATE KEY UPDATE
                              visitor_ip = '$visitor_ip',
                              last_visit = NOW(),
                              total_visits = total_visits + 1");

    // Update active users (online now), keyed on session_id for the same reason as above.
    mysqli_query($conn, "INSERT INTO active_users (session_id, visitor_ip, current_page, last_activity)
                          VALUES ('$session_id', '$visitor_ip', '$page_url', NOW())
                          ON DUPLICATE KEY UPDATE
                              visitor_ip = '$visitor_ip',
                              current_page = '$page_url',
                              last_activity = NOW()");

    // Clean up old active users (inactive for more than 5 minutes)
    mysqli_query($conn, "DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
}

// Get total page views
function getTotalPageViews($conn, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM website_analytics $where");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get unique visitors count — computed from website_analytics (which carries page_url) rather
// than the separate unique_visitors table, so it can be scoped to this site's own traffic.
function getUniqueVisitors($conn, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM website_analytics $where");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get active users (online now)
function getActiveUsers($conn) {
    // First, clean up old inactive users (more than 5 minutes old) — harmless to run across all
    // sites sharing this table, since it only removes stale rows, never real current activity.
    mysqli_query($conn, "DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

    // Then count remaining active users on this site specifically
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM active_users WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND (" . analyticsPathCondition('current_page') . ")");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get popular pages
function getPopularPages($conn, $limit = 10, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT page_url, COUNT(*) as views
            FROM website_analytics
            $where
            GROUP BY page_url
            ORDER BY views DESC
            LIMIT $limit";

    return mysqli_query($conn, $sql);
}

// Get device statistics
function getDeviceStats($conn, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT device_type, COUNT(*) as count
            FROM website_analytics
            $where
            GROUP BY device_type
            ORDER BY count DESC";

    return mysqli_query($conn, $sql);
}

// Get browser statistics
function getBrowserStats($conn, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT browser, COUNT(*) as count
            FROM website_analytics
            $where
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 5";

    return mysqli_query($conn, $sql);
}

// Get hourly traffic for today
function getHourlyTraffic($conn) {
    $sql = "SELECT HOUR(visit_time) as hour, COUNT(*) as visits
            FROM website_analytics
            WHERE visit_date = CURDATE() AND " . getAnalyticsScopeCondition() . "
            GROUP BY HOUR(visit_time)
            ORDER BY hour";

    return mysqli_query($conn, $sql);
}

// Get daily traffic for last 30 days
function getDailyTraffic($conn, $days = 30) {
    $sql = "SELECT visit_date, COUNT(*) as visits
            FROM website_analytics
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY) AND " . getAnalyticsScopeCondition() . "
            GROUP BY visit_date
            ORDER BY visit_date";

    return mysqli_query($conn, $sql);
}

// Get traffic sources (referrers)
function getTrafficSources($conn, $limit = 10, $date_range = 'all') {
    $where = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT
            CASE
                WHEN referrer = 'Direct' THEN 'Direct Traffic'
                WHEN referrer LIKE '%google%' THEN 'Google Search'
                WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                ELSE 'Other Websites'
            END as source,
            COUNT(*) as visits
            FROM website_analytics
            $where
            GROUP BY source
            ORDER BY visits DESC
            LIMIT $limit";

    return mysqli_query($conn, $sql);
}

// Get bounce rate (visitors who viewed only one page)
function getBounceRate($conn, $date_range = 'all') {
    $where_date = "WHERE " . getAnalyticsScopeCondition();
    if ($date_range == 'today') {
        $where_date .= " AND visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where_date .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where_date .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Count visitors who viewed only 1 unique page (bounce)
    // vs total unique visitors
    $sql = "SELECT
            SUM(CASE WHEN page_count = 1 THEN 1 ELSE 0 END) as bounced,
            COUNT(*) as total
            FROM (
                SELECT visitor_ip, COUNT(DISTINCT page_url) as page_count
                FROM website_analytics
                $where_date
                GROUP BY visitor_ip
            ) as visitor_stats";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row['total'] > 0 && $row['bounced'] !== null) {
        return round(($row['bounced'] / $row['total']) * 100, 2);
    }
    return 0;
}
?>
