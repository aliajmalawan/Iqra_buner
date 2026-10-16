<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

// Pages offered in the "Link to" picker, kept in sync with the site's actual pages.
const BUILT_IN_PAGES = [
    'index.php'            => 'Home',
    'about.php'             => 'About',
    'mission-vision.php'    => 'Mission & Vision',
    'core-values.php'       => 'Core Values',
    'leadership.php'        => 'Leadership',
    'courses.php'           => 'Courses',
    'faculty.php'           => 'Faculty',
    'examination.php'       => 'Examination',
    'admission.php'         => 'Admission',
    'downloads.php'         => 'Downloads',
    'notifications.php'     => 'Notifications',
    'campuses.php'          => 'Campuses',
    'campus-portal.php'     => 'Campus Portal',
    'events.php'            => 'Events',
    'news.php'              => 'News',
    'alumni.php'            => 'Alumni',
    'gallery.php'           => 'Gallery',
    'contact.php'           => 'Contact',
];

$message = '';

// Structure list: bulk save from the drag / indent / outdent controls
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['structure_save'])) {
    $ids = $_POST['order'] ?? [];
    $depths = $_POST['depth'] ?? [];

    $current_parent = null;
    $top_order = 0;
    $child_order = 0;
    foreach ($ids as $i => $id) {
        $id = intval($id);
        $depth = intval($depths[$i] ?? 0);
        // A depth-1 row with no top-level row above it (shouldn't happen — the
        // client-side clamp prevents it) safely falls back to top-level.
        if ($depth === 0 || $current_parent === null) {
            $top_order++;
            $child_order = 0;
            mysqli_query($conn, "UPDATE menu_items SET parent_id = NULL, display_order = $top_order WHERE id = $id");
            $current_parent = $id;
        } else {
            $child_order++;
            mysqli_query($conn, "UPDATE menu_items SET parent_id = $current_parent, display_order = $child_order WHERE id = $id");
        }
    }
    $message = "Menu structure updated!";
}

// Delete (cascades to sub-items when deleting a top-level item)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM menu_items WHERE parent_id = $id");
    if (mysqli_query($conn, "DELETE FROM menu_items WHERE id = $id")) {
        $message = "Menu item deleted successfully!";
    } else {
        $message = "Error deleting menu item.";
    }
}

// Add / edit a single item — parent/order are set only via the Structure list above,
// never by this form: a new item always starts top-level, editing one leaves its
// current position untouched.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['label'])) {
    $label = trim($_POST['label'] ?? '');
    $icon = trim($_POST['icon'] ?? '') ?: 'fa-circle';
    $open_new_tab = ($_POST['target'] ?? '_self') === '_blank' ? 1 : 0;
    $status = isset($_POST['is_active']) ? 'active' : 'inactive';

    $link_target = $_POST['link_target'] ?? '';
    if ($link_target === 'custom') {
        $url = trim($_POST['custom_url'] ?? '');
    } elseif ($link_target === 'none') {
        $url = '';
    } else {
        $url = $link_target;
    }

    if ($label === '') {
        $message = "Label is required.";
    } else {
        $label_e = mysqli_real_escape_string($conn, $label);
        $icon_e = mysqli_real_escape_string($conn, $icon);
        $url_e = mysqli_real_escape_string($conn, $url);

        if (isset($_POST['menu_id']) && !empty($_POST['menu_id'])) {
            $id = intval($_POST['menu_id']);
            $q = "UPDATE menu_items SET label='$label_e', icon='$icon_e', url='$url_e', open_new_tab=$open_new_tab, status='$status' WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Menu item updated successfully!" : "Error updating menu item.";
        } else {
            $next_order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(display_order),0)+1 as n FROM menu_items WHERE parent_id IS NULL"))['n'];
            $q = "INSERT INTO menu_items (label, icon, url, parent_id, open_new_tab, display_order, status) VALUES ('$label_e', '$icon_e', '$url_e', NULL, $open_new_tab, $next_order, '$status')";
            $message = mysqli_query($conn, $q) ? "Menu item added successfully!" : "Error adding menu item.";
        }
    }
}

// Item being edited
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM menu_items WHERE id = $edit_id"));
}

// Flatten the tree one level deep into draggable rows, each carrying its depth (0 or 1)
$rows = [];
$top_level = [];
$tq = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id IS NULL ORDER BY display_order ASC, id ASC");
if ($tq) { while ($row = mysqli_fetch_assoc($tq)) { $top_level[] = $row; } }

foreach ($top_level as $t) {
    $rows[] = $t + ['depth' => 0];
    $cq = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id = {$t['id']} ORDER BY display_order ASC, id ASC");
    if ($cq) {
        while ($child = mysqli_fetch_assoc($cq)) {
            $rows[] = $child + ['depth' => 1];
        }
    }
}

// Which "Link to" option should be pre-selected, and is the current URL a free-form
// one that doesn't match anything in the picker (so it falls back to "Custom URL")?
$current_url = $edit_item['url'] ?? '';
$is_known = $current_url === '' || array_key_exists($current_url, BUILT_IN_PAGES);
$current_target = $current_url === '' ? ($edit_item ? 'none' : '') : ($is_known ? $current_url : 'custom');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Builder - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .menu-builder-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) { .menu-builder-grid { grid-template-columns: 1fr; } }

        .menu-row {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 6px;
            background: #fff;
            cursor: grab;
        }
        .menu-row.is-inactive { opacity: 0.55; }
        .menu-row.is-dragging { opacity: 0.35; }
        .menu-row-grip { color: var(--text-light); cursor: grab; font-size: 15px; flex-shrink: 0; }
        .menu-row-info { flex: 1; min-width: 0; }
        .menu-row-title { font-weight: 700; color: var(--primary-color); font-size: 13.5px; }
        .menu-row-meta { display: block; font-size: 11.5px; color: var(--text-light); }
        .menu-row-btn {
            border: 1px solid var(--border-color); background: #fff; border-radius: 6px;
            width: 28px; height: 28px; flex-shrink: 0; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-color); font-size: 12px; text-decoration: none;
        }
        .menu-row-btn:hover { background: var(--background-color); }
        .menu-row-delete:hover { background: #DC2626; color: #fff; border-color: #DC2626; }

        #structure-dirty {
            display: none; margin-left: 10px; background: #F59E0B; color: #fff;
            font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
        }
        .help-note { color: var(--text-light); font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-bars"></i> Menu Builder</h1>
        </div>
        <p class="help-note">Manage the links shown in the website's top navigation bar.</p>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="menu-builder-grid">
            <!-- Structure -->
            <div class="card">
                <h2 style="color: var(--primary-color); margin-bottom: 14px;">Structure</h2>

                <?php if (empty($rows)): ?>
                    <div style="text-align: center; padding: 50px 20px; color: var(--text-light);">
                        <i class="fas fa-bars" style="font-size: 60px; margin-bottom: 16px; opacity: 0.3;"></i>
                        <h3 style="margin-bottom: 8px;">This menu is empty</h3>
                        <p>Add the first item using the form on the right.</p>
                    </div>
                <?php else: ?>
                    <p class="help-note">
                        Drag <strong>⠿</strong> to reorder &middot; <strong>⇥</strong> makes an item a sub-item of the one above &middot;
                        <strong>⇤</strong> moves it back out &middot; then press <strong>Save Structure</strong>.
                    </p>

                    <form method="POST" id="structure-form"><?php echo csrf_field(); ?>
                        <input type="hidden" name="structure_save" value="1">
                        <div id="menu-tree">
                            <?php foreach ($rows as $row): ?>
                                <div class="menu-row<?php echo $row['status'] !== 'active' ? ' is-inactive' : ''; ?>" draggable="true"
                                     data-id="<?php echo $row['id']; ?>" data-depth="<?php echo $row['depth']; ?>"
                                     style="margin-left: <?php echo $row['depth'] * 28; ?>px;">
                                    <span class="menu-row-grip" title="Drag to reorder">⠿</span>
                                    <span class="menu-row-info">
                                        <span class="menu-row-title"><i class="fas <?php echo htmlspecialchars($row['icon']); ?>"></i> <?php echo htmlspecialchars($row['label']); ?></span>
                                        <span class="menu-row-meta">
                                            <?php echo $row['url'] !== '' ? htmlspecialchars($row['url']) : 'No link (dropdown heading)'; ?>
                                            <?php echo $row['open_new_tab'] ? ' &middot; new tab' : ''; ?>
                                            <?php echo $row['status'] !== 'active' ? ' &middot; hidden' : ''; ?>
                                        </span>
                                    </span>
                                    <button type="button" class="menu-row-btn menu-outdent" title="Move out a level">⇤</button>
                                    <button type="button" class="menu-row-btn menu-indent" title="Make sub-item">⇥</button>
                                    <a class="menu-row-btn" href="?edit=<?php echo $row['id']; ?>" title="Edit"><i class="fas fa-pen"></i></a>
                                    <button type="button" class="menu-row-btn menu-row-delete" data-id="<?php echo $row['id']; ?>" data-label="<?php echo htmlspecialchars(addslashes($row['label'])); ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                    <input type="hidden" name="order[]" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="depth[]" value="<?php echo $row['depth']; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 14px;">Save Structure</button>
                        <span id="structure-dirty">Unsaved changes</span>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Add / Edit -->
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 14px;">
                    <h2 style="color: var(--primary-color);"><?php echo $edit_item ? 'Edit Item' : 'Add Item'; ?></h2>
                    <?php if ($edit_item): ?><a href="menu_builder.php">Cancel</a><?php endif; ?>
                </div>

                <form method="POST"><?php echo csrf_field(); ?>
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="menu_id" value="<?php echo $edit_item['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="label" id="f-label" required value="<?php echo $edit_item ? htmlspecialchars($edit_item['label']) : ''; ?>" placeholder="Admissions">
                        <small style="color: var(--text-light); font-size: 12px;">The text visitors see. Keep it to one or two words.</small>
                    </div>

                    <div class="form-group">
                        <label>Link to</label>
                        <select name="link_target" id="link-target">
                            <optgroup label="Built-in pages">
                                <?php foreach (BUILT_IN_PAGES as $path => $label): ?>
                                <option value="<?php echo htmlspecialchars($path); ?>" <?php echo $current_target === $path ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Other">
                                <option value="none" <?php echo $current_target === 'none' ? 'selected' : ''; ?>>Dropdown heading (no link)</option>
                                <option value="custom" <?php echo $current_target === 'custom' ? 'selected' : ''; ?>>Custom URL&hellip;</option>
                            </optgroup>
                        </select>
                        <small style="color: var(--text-light); font-size: 12px;">Choosing a page fills the label in for you when it's still blank.</small>
                    </div>

                    <div class="form-group" id="custom-url-group" <?php echo $current_target === 'custom' ? '' : 'style="display:none"'; ?>>
                        <label>Custom URL</label>
                        <input type="text" name="custom_url" id="f-custom-url" value="<?php echo $current_target === 'custom' ? htmlspecialchars($current_url) : ''; ?>" placeholder="https://... or gallery.php?category=events">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="auto_AdminCP_menu_builder_php_1">Icon (Font Awesome class)</label>
                            <input id="auto_AdminCP_menu_builder_php_1" type="text" name="icon" value="<?php echo $edit_item ? htmlspecialchars($edit_item['icon']) : 'fa-circle'; ?>" placeholder="fa-circle">
                        </div>
                        <div class="form-group">
                            <label for="auto_AdminCP_menu_builder_php_2">Opens In</label>
                            <select id="auto_AdminCP_menu_builder_php_2" name="target">
                                <option value="_self" <?php echo (!$edit_item || !$edit_item['open_new_tab']) ? 'selected' : ''; ?>>Same tab</option>
                                <option value="_blank" <?php echo ($edit_item && $edit_item['open_new_tab']) ? 'selected' : ''; ?>>New tab</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 400;">
                            <input type="checkbox" name="is_active" value="1" style="width: auto;" <?php echo (!$edit_item || $edit_item['status'] === 'active') ? 'checked' : ''; ?>>
                            Visible on the website
                        </label>
                    </div>

                    <div style="display:flex; gap: 10px; align-items:center;">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_item ? 'Update Item' : 'Add Item'; ?></button>
                        <?php if ($edit_item): ?>
                            <a href="?action=delete&id=<?php echo $edit_item['id']; ?>" class="btn btn-primary" style="background:#DC2626;" onclick="return confirm('Delete &quot;<?php echo htmlspecialchars(addslashes($edit_item['label'])); ?>&quot;<?php echo empty($edit_item['parent_id']) ? ' and any sub-items beneath it' : ''; ?>?')">Delete</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        "use strict";

        // Delete from the Structure list
        document.querySelectorAll('.menu-row-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var label = btn.getAttribute('data-label') || 'this item';
                if (!confirm('Delete "' + label + '" and any sub-items beneath it?')) return;
                window.location = '?action=delete&id=' + btn.getAttribute('data-id') + '&csrf_token=' + encodeURIComponent(window.__CSRF_TOKEN || '');
            });
        });

        var tree = document.getElementById('menu-tree');
        if (!tree) return;

        var INDENT = 28;
        var dragging = null;
        var dirty = document.getElementById('structure-dirty');

        var rows = function () { return Array.prototype.slice.call(tree.querySelectorAll('.menu-row')); };
        var depthOf = function (row) { return parseInt(row.getAttribute('data-depth') || '0', 10); };
        var setDepth = function (row, depth) {
            row.setAttribute('data-depth', String(depth));
            row.style.marginLeft = (depth * INDENT) + 'px';
            row.querySelector('input[name="depth[]"]').value = String(depth);
        };

        // Only two levels exist on the public site (top level + one dropdown level).
        var clamp = function () {
            var previous = -1;
            rows().forEach(function (row) {
                var depth = Math.max(0, Math.min(depthOf(row), previous + 1, 1));
                setDepth(row, depth);
                previous = depth;
            });
        };

        var markDirty = function () { if (dirty) dirty.style.display = 'inline-block'; };

        rows().forEach(function (row) {
            row.addEventListener('dragstart', function () {
                dragging = row;
                setTimeout(function () { row.classList.add('is-dragging'); }, 0);
            });
            row.addEventListener('dragend', function () {
                row.classList.remove('is-dragging');
                dragging = null;
                clamp();
                markDirty();
            });
            row.querySelector('.menu-indent').addEventListener('click', function () {
                setDepth(row, depthOf(row) + 1);
                clamp();
                markDirty();
            });
            row.querySelector('.menu-outdent').addEventListener('click', function () {
                setDepth(row, Math.max(0, depthOf(row) - 1));
                clamp();
                markDirty();
            });
        });

        tree.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragging) return;
            var before = null;
            rows().forEach(function (row) {
                if (row === dragging || before !== null) return;
                var box = row.getBoundingClientRect();
                if (e.clientY < box.top + box.height / 2) before = row;
            });
            if (before) {
                tree.insertBefore(dragging, before);
            } else {
                tree.appendChild(dragging);
            }
        });

        var form = document.getElementById('structure-form');
        if (form) form.addEventListener('submit', clamp);

        // Fill the label from the chosen page, and reveal the custom URL field.
        var select = document.getElementById('link-target');
        var customGroup = document.getElementById('custom-url-group');
        var label = document.getElementById('f-label');
        if (select && customGroup) {
            select.addEventListener('change', function () {
                customGroup.style.display = select.value === 'custom' ? '' : 'none';
                if (select.value === 'custom') {
                    document.getElementById('f-custom-url').focus();
                    return;
                }
                if (label && label.value.trim() === '' && select.value !== 'none') {
                    label.value = select.options[select.selectedIndex].text;
                }
            });
        }
    })();
    </script>
    </div>
</body>
</html>
