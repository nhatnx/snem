<?php

class Database
{
    /** @var string variable to hold the query sql */
    private string $_sql = '';
    /** @var string variable to hold the prefix used on all database tables */
    private string $_table_prefix = '';
    /** @var bool|mysqli variable to hold the connector resource */
    private $_resource = '';
    /** @var int The limit for the query */
    private int $_limit = 0;
    /** @var int The for offset for the limit */
    private int $_offset = 0;

    /**
     * Database object constructor
     * @param string Database type ($type='mysql', ) --> UPDATE LATER
     * @param string Database host
     * @param string Database user name
     * @param string Database user password
     * @param string Database name
     * @param string Common prefix for all tables
     */
    public function __construct($host, $user, $pass, $db = '', $table_prefix = '')
    {
        $this->_resource = mysqli_connect($host, $user, $pass);
        if ($this->_resource) {
            if (mysqli_select_db($this->_resource, $db)) {
                $this->_table_prefix = $table_prefix;
                $this->_resource->query("SET NAMES 'utf8'");

//                if ($tzOffset = _TZ_OFFSET) {
//                    // _TZ_OFFSET is in language file
//                    @mysqli_query(
//                        $this->_resource,
//                        "SET time_zone = '" . ((_TZ_OFFSET > 0) ? "+" : "") . _TZ_OFFSET . ":00'"
//                    );
//                }
                return true;
            } else {
                $this->traceERROR();
                return false;
            }
        }

        $this->traceERROR();
        return false;
    }

    /**
     * Print SQL error message
     * @param boolean If TRUE, displays the SQL statement sent to the database
     */
    public function traceERROR($showSQL = false)
    {
        echo "<p><span style='color:red'><strong>SQL ERROR: </strong>" . mysqli_error($this->_resource) . "</span>" .
            ($showSQL ? "<br />SQL = <pre>$this->_sql</pre>" : '') . "</p>";

        return;
    }

    /**
     * Close database connection
     */
    public function closeConnection()
    {
        mysqli_close($this->_resource);

        return;
    }

    /**
     * @return string The current value of the SQL variable
     */
    public function getQuery()
    {
        return "<pre>" . htmlspecialchars($this->_sql) . "</pre>";
    }

    /**
     * @return string The database prefix
     */
    public function getPrefix()
    {
        return $this->_table_prefix;
    }

    /**
     * Get the ID generated from the previous INSERT operation
     * @return int The ID generated for an AUTO_INCREMENT column
     */
    public function insertId()
    {
        return mysqli_insert_id($this->_resource);
    }

    /**
     * Get the NULL date used for inserting or updating database
     * @return string The NULL date
     */
    public function nullDate()
    {
        return "0000-00-00 00:00:00";
    }

    /**
     * Sets the SQL query string for later execution.
     *
     * This function replaces a string identifier <var>$prefix</var> with the
     * string held is the <var>_table_prefix</var> class variable.
     *
     * @param string $sql The SQL query
     * @param int $offset The offset to start selection
     * @param int $limit The number of results to return
     * @param string $prefix The common table prefix
     */
    public function setQuery($sql, $offset = 0, $limit = 0, $prefix = '#__')
    {
        $this->_sql = $this->replacePrefix($sql, $prefix);
        $this->_limit = intval($limit);
        $this->_offset = intval($offset);

        return;
    }

    /**
     * Set the safe SQL query string
     * @return string A SQL query string in safety.
     */
    public function safeMode(/* $query, [$args...]*/)
    {
        $args = func_get_args();
        $query = array_shift($args);
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }
        $args = array_map('mysqli_real_escape_string', [$this->_resource], $args);
        $query = vsprintf($query, $args);

        return $query;
    }

    /**
     * This function replaces a string identifier <var>$prefix</var> with the
     * string held is the <var>_table_prefix</var> class variable.
     *
     * @param string $sql The SQL query
     * @param string $prefix The common table prefix
     * @return string
     */
    public function replacePrefix($sql, $prefix = '#__')
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

            $literal .= str_replace($prefix, $this->_table_prefix, substr($sql, $startPos, $j - $startPos));
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

    /**
     * Execute the query
     * @param null|string $sql
     * @param int $offset
     * @param int $limit
     * @param string $prefix
     * @return mixed A database resource if successful, FALSE if not.
     */
    public function query($sql = null, $offset = 0, $limit = 0, $prefix = '#__')
    {
        if (!empty($sql)) {
            $this->setQuery($sql, $offset, $limit, $prefix);
        }

        if ($this->_limit > 0 || $this->_offset > 0) {
            $this->_sql .= "\nLIMIT $this->_offset, $this->_limit";
        }
        $cursor = $this->_resource->query($this->_sql);
        if (!$cursor) {
            return false;
        }

        return $cursor;
    }

    /**
     * This method loads the first field of the first row returned by the query.
     * @param null|string $sql
     * @return mixed|null Value returned in the query or null if the query failed.
     */
    public function execScalar($sql = null)
    {
        if (!($cur = $this->query($sql))) {
            $this->traceERROR();
            return null;
        }

        $a_ret = null;
        if ($a_row = mysqli_fetch_row($cur)) {
            $a_ret = $a_row[0];
        }
        mysqli_free_result($cur);
        return $a_ret;
    }

    /**
     * This method loads the result returned by the query as an object.
     * @param null|string $sql
     * @return array Object returned in the query or null if the query failed.
     */
    public function execQuery($sql = null)
    {
        if (!($cur = $this->query($sql))) {
            $this->traceERROR();
            return null;
        }

        $a_ret = array();
        while ($a_row = mysqli_fetch_object($cur)) {
            $a_ret[] = $a_row;
        }

        mysqli_free_result($cur);
        return $a_ret;
    }

    /**
     * Execute an insert/update query.
     * @param null|string $sql
     * @return bool|null
     */
    public function execNonQuery($sql = null)
    {
        if (!$this->query($sql)) {
            $this->traceERROR();
            return null;
        }

        return true;
    }

    /**
     * This method loads the first row returned by the query.
     * @param null|string $sql
     * @return object|null First row of the query.
     */
    public function execRow($sql = null)
    {
        if (!($cur = $this->query($sql))) {
            $this->traceERROR();
            return null;
        }

        $a_ret = null;
        if ($a_row = mysqli_fetch_object($cur)) {
            $a_ret = $a_row;
        }

        mysqli_free_result($cur);
        return $a_ret;
    }

    /**
     * This method loads a list of database rows (numeric column indexing)
     * @param string $key
     * @param null|string $sql
     * @return array If <var>key</var> is empty as sequential list of returned records.
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     */
    public function execColumn($key = '', $sql = null)
    {
        if (!($cur = $this->query($sql))) {
            $this->traceERROR();
            return null;
        }

        $a_ret = array();
        while ($a_row = mysqli_fetch_assoc($cur)) {
            if ($key) {
                $a_ret[$a_row[$key]] = $a_row;
            } else {
                $a_ret[] = $a_row;
            }
        }

        mysqli_free_result($cur);
        return $a_ret;
    }
}
