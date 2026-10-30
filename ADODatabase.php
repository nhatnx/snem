<?php

namespace app\core;

if (!defined('ADODB_ASSOC_CASE')) {
    define('ADODB_ASSOC_CASE', ADODB_ASSOC_CASE_NATIVE);
}

// ADOdb echoes its messages (e.g. mysqli's "SQL Statement failed on
// preparation") straight into the page; route them through ADODatabase::outp.
if (!defined('ADODB_OUTP')) {
    define('ADODB_OUTP', 'app\core\ADODatabase::outp');
}

use ADOConnection;

/**
 * Wraps the driver-specific ADOConnection. Calls and property accesses not
 * handled here are forwarded to the live connection, so this object can be
 * used anywhere a plain ADOConnection would be.
 */
class ADODatabase
{
    private ADOConnection $conn;

    /** @var string variable to hold the prefix used on all database tables */
    private string $tablePrefix = '';

    public static int $initFetchMode = ADODB_FETCH_ASSOC;

    /** @var bool echo ADOdb messages like ADOdb does, instead of logging them (debug mode) */
    public static bool $echoOutput = false;

    /**
     * Database object constructor
     *
     * @param string $driver Database driver ($driver='mysqli', )
     * @param string $host Database host
     * @param string $user Database username
     * @param string $pass Database user password
     * @param string $db Database name
     * @param string $tablePrefix Common prefix for all tables
     * @param bool $debugMode Enable debug mode
     */
    public function __construct(
        string $driver,
        string $host,
        string $user,
        string $pass,
        string $db = '',
        string $tablePrefix = '',
        bool $debugMode = false
    ) {
        $this->conn = newADOConnection($driver);

        // ADOdb reports failures by returning false; turn them into
        // exceptions. Set before connect() so a failed connection throws too,
        // and on the connection itself so Active Record queries are covered.
        $this->conn->raiseErrorFn = [self::class, 'raiseError'];
        // Drivers cache the last error and some failure paths (mysqli prepare
        // errors, placeholder mismatches) don't refresh it, so clear it before
        // every query. LogSQL() takes this hook over; errors still throw then,
        // but those paths may report a stale message.
        $this->conn->fnExecute = [self::class, 'clearError'];

        if ($driver === 'mysqli') {
            $this->conn->setConnectionParameter(MYSQLI_SET_CHARSET_NAME, 'utf8mb4');
        }
        $this->conn->connect($host, $user, $pass, $db);
        $this->conn->debug = self::$echoOutput = $debugMode;
        $this->conn->setFetchMode(self::$initFetchMode = ADODB_FETCH_ASSOC);

        $this->tablePrefix = $tablePrefix;
    }

    public function getConn(): ADOConnection
    {
        return $this->conn;
    }

    /**
     * ADOdb error callback (see `raiseErrorFn`). For CONNECT, $p1/$p2 are the
     * host and database; for query operations $p1 is the SQL.
     *
     * @throws SEDbException
     */
    public static function raiseError($dbms, $fn, $errno, $errmsg, $p1 = null, $p2 = null, $conn = null): void
    {
        $sql = ($fn !== 'CONNECT' && is_string($p1)) ? $p1 : null;

        throw new SEDbException((string)$fn, (string)$errmsg, (int)$errno, $sql);
    }

    /**
     * ADOdb output handler (see `ADODB_OUTP`). Writes ADOdb's messages to the
     * PHP error log instead of the page; in debug mode they are echoed as
     * ADOdb would, since the SQL trace is meant to be seen there.
     */
    public static function outp($msg, $newline = true): void
    {
        if (self::$echoOutput) {
            echo $newline ? "{$msg}<br>\n" : $msg;

            return;
        }

        $text = trim(html_entity_decode(strip_tags((string)$msg), ENT_QUOTES));
        if ($text !== '') {
            error_log("ADOdb: {$text}");
        }
    }

    /**
     * ADOdb pre-execute hook (see `fnExecute`): drops the cached error so
     * errorMsg()/errorNo() describe this query, not an earlier one. Returns
     * null so the query proceeds.
     */
    public static function clearError(ADOConnection $conn, $sql = null, $inputarr = null)
    {
        $conn->_errorMsg = false;
        $conn->_errorCode = false;

        return null;
    }

    /**
     * Throws for a failed query result. Driver errors have already thrown via
     * raiseError(), so this only catches the ADOdb paths that return false
     * without invoking `raiseErrorFn` (e.g. a placeholder count mismatch) —
     * errorMsg() would be stale there. Skipped while errors are suppressed via
     * IgnoreErrors().
     *
     * @throws SEDbException
     */
    private function assertResult($rs, string $operation, string $sql): void
    {
        if ($rs === false && $this->conn->raiseErrorFn) {
            throw new SEDbException($operation, '', 0, $sql);
        }
    }

    public function __call($method, $args)
    {
        return $this->conn->$method(...$args);
    }

    public function __get($property)
    {
        return $this->conn->$property;
    }

    public function __set($property, $value)
    {
        $this->conn->$property = $value;

        // `$db->debug = true` should still print the SQL trace on the page
        if ($property === 'debug') {
            self::$echoOutput = (bool)$value;
        }
    }

    public function __isset($property)
    {
        return isset($this->conn->$property);
    }

    public function setFetchMode($mode = ADODB_FETCH_ASSOC): void
    {
        $this->conn->setFetchMode($mode);
    }

    public function resetFetchMode(): void
    {
        $this->conn->setFetchMode(self::$initFetchMode);
    }

    /**
     * @return string The database prefix
     */
    public function getPrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * This function replaces a string identifier <var>$prefix</var> with the
     * string held is the <var>_table_prefix</var> class variable.
     *
     * @param string $sql The SQL query
     * @param string $prefix The common table prefix
     * @return string
     */
    public function replacePrefix(string $sql, string $prefix = '#__'): string
    {
        $sql = trim($sql);

        $escaped = false;
        $quoteChar = '';

        $n = strlen($sql);

        $startPos = 0;
        $literal = '';
        while ($startPos < $n) {
            $ip = strpos($sql, $prefix, $startPos);
            if ($ip === false) {
                break;
            }

            $j = strpos($sql, "'", $startPos);
            $k = strpos($sql, '"', $startPos);
            if (($k !== false) && (($k < $j) || ($j === false))) {
                $quoteChar = '"';
                $j = $k;
            } else {
                $quoteChar = "'";
            }

            if ($j === false) {
                $j = $n;
            }

            $literal .= str_replace($prefix, $this->tablePrefix, substr($sql, $startPos, $j - $startPos));
            $startPos = $j;

            $j = $startPos + 1;

            if ($j >= $n) {
                break;
            }

            // quote comes first, find end of quote
            while (true) {
                $k = strpos($sql, $quoteChar, $j);
                $escaped = false;
                if ($k === false) {
                    break;
                }
                $l = $k - 1;
                while ($l >= 0 && $sql[$l] == '\\') {
                    $l--;
                    $escaped = !$escaped;
                }
                if ($escaped) {
                    $j = $k + 1;
                    continue;
                }
                break;
            }
            if ($k === false) {
                // error in the query - no end quote; ignore it
                break;
            }
            $literal .= substr($sql, $startPos, $k - $startPos + 1);
            $startPos = $k + 1;
        }
        if ($startPos < $n) {
            $literal .= substr($sql, $startPos, $n - $startPos);
        }
        return $literal;
    }

    public function checkConnection(): bool
    {
        return $this->isConnected();
    }

    /**
     * Get the NULL date used for inserting or updating database
     * @return string The NULL date
     */
    public function nullDate(): string
    {
        return "0000-00-00 00:00:00";
    }

    public function getData($type, $sql, $inputArr = false, $fetchDTO = true, $trim = false)
    {
        $sql = $this->replacePrefix($sql);

        switch ($type) {
            case 'getCol':
            {
                $rs = $this->conn->$type($sql, $inputArr, $trim);

                break;
            }
            case 'getRow':
            case 'getOne':
            case 'getAll':
            default:
            {
                $rs = $this->conn->$type($sql, $inputArr);

                break;
            }
        }

        // Every get* returns false only on failure (no rows is null/[])
        $this->assertResult($rs, strtoupper($type), $sql);

        if (!$fetchDTO || empty($rs)) {
            return $rs;
        }

        return json_decode(json_encode($rs), false);
    }

    public function getAll($sql, $inputarr = false, $fetchDTO = true)
    {
        return $this->getData('getAll', $sql, $inputarr, $fetchDTO);
    }

    public function getRow($sql, $inputarr = false, $fetchDTO = true)
    {
        return $this->getData('getRow', $sql, $inputarr, $fetchDTO);
    }

    /**
     * Get first column of each row of the recordset
     *
     * @param $sql
     * @param $inputarr
     * @param $trim
     * @param $fetchDTO
     * @return array|bool|mixed
     */
    public function getCol($sql, $inputarr = false, $trim = false, $fetchDTO = true)
    {
        return $this->getData('getCol', $sql, $inputarr, $fetchDTO, $trim);
    }

    public function getOne($sql, $inputarr = false, $fetchDTO = true)
    {
        return $this->getData('getOne', $sql, $inputarr, $fetchDTO);
    }

    /**
     * @return \ADORecordSet
     * @throws SEDbException when the query fails
     */
    public function execute($sql, $inputarr = false)
    {
        $sql = $this->replacePrefix($sql);

        $rs = $this->conn->execute($sql, $inputarr);
        $this->assertResult($rs, 'EXECUTE', $sql);

        return $rs;
    }

    /**
     * Drivers' genID() creates a missing sequence by letting the first query
     * fail, so errors are suppressed for the call and a failed result thrown.
     *
     * @throws SEDbException
     */
    public function genID($seqName = 'adodbseq', $startID = 1)
    {
        $saved = $this->conn->IgnoreErrors();
        try {
            $id = $this->conn->genID($seqName, $startID);
        } finally {
            $this->conn->IgnoreErrors($saved);
        }

        if (!$id && $this->conn->raiseErrorFn) {
            throw new SEDbException('GENID', (string)$this->conn->errorMsg(), (int)$this->conn->errorNo());
        }

        return $id;
    }

    public function getInsertId()
    {
        return $this->conn->insert_Id();
    }
}
