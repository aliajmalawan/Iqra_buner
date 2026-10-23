<?php
/**
 * Prepared-statement results that work with or without the mysqlnd driver.
 *
 * mysqli_stmt_get_result() only exists when PHP's mysqli is built on mysqlnd.
 * Many cPanel / CloudLinux servers ship the plain "mysqli" extension instead
 * (mysqlnd is a separate "nd_mysqli" option), where calling it is a fatal
 * error. db_stmt_result() returns the native mysqli_result when available and
 * an equivalent in-memory result otherwise; db_fetch_assoc / db_fetch_row /
 * db_num_rows accept either kind (and plain mysqli_query() results).
 */

class DbStmtResult {
    public $num_rows = 0;
    private $rows = [];
    private $pos = 0;

    public function __construct(array $rows) { $this->rows = $rows; $this->num_rows = count($rows); }
    public function fetch_assoc() { return $this->pos < $this->num_rows ? $this->rows[$this->pos++] : null; }
    public function fetch_row() { $r = $this->fetch_assoc(); return $r === null ? null : array_values($r); }
}

function db_stmt_result($stmt) {
    if (function_exists('mysqli_stmt_get_result') && !defined('DB_COMPAT_FORCE_FALLBACK')) {
        return mysqli_stmt_get_result($stmt);
    }
    // Fallback without mysqlnd: bind every column to a variable and copy each row out
    mysqli_stmt_store_result($stmt);
    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) return new DbStmtResult([]);
    $row = []; $refs = [];
    foreach (mysqli_fetch_fields($meta) as $field) { $row[$field->name] = null; $refs[] = &$row[$field->name]; }
    mysqli_free_result($meta);
    mysqli_stmt_bind_result($stmt, ...$refs);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $copy = [];
        foreach ($row as $k => $v) $copy[$k] = $v;   // copy values, not the bound references
        $rows[] = $copy;
    }
    mysqli_stmt_free_result($stmt);
    return new DbStmtResult($rows);
}

function db_fetch_assoc($res) {
    if (!$res) return null;
    return $res instanceof mysqli_result ? mysqli_fetch_assoc($res) : $res->fetch_assoc();
}

function db_fetch_row($res) {
    if (!$res) return null;
    return $res instanceof mysqli_result ? mysqli_fetch_row($res) : $res->fetch_row();
}

function db_num_rows($res) {
    if (!$res) return 0;
    return $res instanceof mysqli_result ? mysqli_num_rows($res) : $res->num_rows;
}
