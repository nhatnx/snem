<?php

namespace app\core;

define('ADODB_ASSOC_CASE', ADODB_ASSOC_CASE_NATIVE);

use ADOConnection;

class ADODatabase extends ADOConnection
{
    private ADOConnection $conn;

    /** @var string variable to hold the prefix used on all database tables */
    private string $tablePrefix = '';

    public static int $initFetchMode = ADODB_FETCH_ASSOC;

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
        parent::__construct();

        $this->conn = newADOConnection($driver);

        if ($driver === 'mysqli') {
            $this->conn->setConnectionParameter(MYSQLI_SET_CHARSET_NAME, 'utf8mb4');
        }
        $this->conn->connect($host, $user, $pass, $db);
        $this->conn->debug = $debugMode;
        $this->conn->setFetchMode(self::$initFetchMode = ADODB_FETCH_ASSOC);

        $this->tablePrefix = $tablePrefix;
    }

    public function getConn(): ADOConnection
    {
        return $this->conn;
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

    public function getCol($sql, $inputarr = false, $trim = false, $fetchDTO = true)
    {
        return $this->getData('getCol', $sql, $inputarr, $fetchDTO, $trim);
    }

    public function getOne($sql, $inputarr = false, $fetchDTO = true)
    {
        return $this->getData('getOne', $sql, $inputarr, $fetchDTO);
    }

    public function execute($sql, $inputarr = false)
    {
        return $this->conn->execute($sql, $inputarr);
    }
}
