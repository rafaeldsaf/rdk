<?php
namespace RDK;

use RDK\Basic;

class Oracle extends Basic
{

    private string $stringORA;
    private string $userORA;
    private string $passORA;
    private $conn;
    private bool $debug;
    private array $error;
    private array $clob;
    private array $blob;
    private array $where;
    private $rowns;
    private array $bind;
    private string $sql;


    /**
     * @param string $string
     * @param string $user
     * @param string $pass
     * @param bool $debug
     */
    public function __construct(string $string,string $user,string $pass,bool $debug=false)
    {
        $this->stringORA = $string;
        $this->userORA = $user;
        $this->passORA = $pass;

        if ($this->debug == true) {
            \RDK\Basic::logFile('[RDK][Oracle] debug on');
        }
    }

    /**
     * @return void
     */
    public function close(): void
    {
        @oci_close($this->conn);
    }

    public function getError(): array
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getRowns()
    {
        return $this->rowns;
    }

    public function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    /**
     * @return bool
     */
    private function connect(): bool
    {
        if ($this->conn) {
            return true;
        } else {
            if ($this->conn = @oci_connect($this->userORA, $this->passORA, $this->stringORA, 'AL32UTF8')) {
                return true;
            } else {
                $e = oci_error();
                $this->error = $e;
                \RDK\Basic::LogFile('[RDK][Oracle] Error open connection: ' . json_encode($e));
                return false;
            }
        }
    }

    private function clear()
    {
        $this->bind = [];
        $this->blob = [];
        $this->clob = [];
        $this->where = [];
        $this->sql = '';
    }

    private function execSQL($sql,$commit)
    {
        if (count($this->where) > 0) {
            $_where_end = implode(' AND ', $this->where);
            $this->sql = $sql. ' WHERE '.$_where_end;
        }
        else{
            $this->sql = $sql;
        }



        $this->execORA($commit);
    }

    /**
     * @param $value
     * @param $name
     * @param string $field
     * @param string $operator
     * @param bool $need
     * @return void
     */
    private function whereBind($value, $name, string $field = '', string $operator = '=', bool $need = false): void
    {
        if ($field == '') {
            $field = $name;
        }

        if ((($value <> '') and ($value <> '%')) or $need) {
            if (($operator == '=') and (is_array($value))) {
                $operator = 'in';
            }

            if ($operator == 'like') {
                $this->where[] = "{$field} {$operator} :{$name}";
                $this->bind[$name] = "%{$value}%";
            } else if ($operator == 'between') {
                $this->where[] = "{$field} {$operator} :{$name}_v1 AND :{$name}_v2";
                $this->bind["{$name}_v1"] = $value[0];
                $this->bind["{$name}_v2"] = $value[1];
            } else if ($operator == 'in') {
                foreach ($value as $key => $val) {
                    $end[] = ':' . $name . '_' . $key;
                    $this->bind[$name . '_' . $key] = $val;
                }
                $this->where[] = "{$field} {$operator}(" . implode(',', $end) . ")";
            } else {
                if ($value == 'null') {
                    $this->where[] = "({$field} is null or {$field} = '')";
                } else if ($value == 'not null') {
                    $this->where[] = "({$field} is not null or {$field} <> '')";
                } else {
                    if (strpos($value, 'SYSDATE')) {
                        $this->where[] = "{$field} {$operator} {$value}";
                    } else {
                        $this->where[] = "{$field} {$operator} :{$name}";
                        $this->bind[$name] = $value;
                    }
                }
            }
        }
    }
    
    /**
     * @param string $name
     * @param $value
     * @param string $type
     * @return void
     */
    private function inBind(string $name, $value,string $type = ''): void
    {
        $this->bind[$name] = $value;
        if ($type == 'CLOB') {
            $this->clob[] = $name;
        } else if ($type == 'BLOB') {
            $this->blob[] = $name;
        } else if ($type == 'OUT'){
            $this->bind[$name] ='000000000000000';
        }
    }

    /**
     * @param bool $commit
     * @return bool|resource
     */
    private function execORA(bool $commit = true)
    {
        $this->error = [];

        $_lob = false;
        if ($this->connect()) {

            if ($this->sql <> '') {
                $_resultSet = oci_parse($this->conn, $this->sql);
                if (isset($this->bind) and (count($this->bind) > 0)) {
                    foreach ($this->bind as $key => &$value) {
                        if (in_array($key, $this->clob)) {
                            $_lob = $value;
                            $descriptor = oci_new_descriptor($this->conn, OCI_DTYPE_LOB);
                            oci_bind_by_name($_resultSet, ":{$key}", $descriptor, -1, OCI_B_CLOB);
                        } else if (in_array($key, $this->blob)) {
                            $_lob = $value;
                            $descriptor = oci_new_descriptor($this->conn, OCI_DTYPE_LOB);
                            oci_bind_by_name($_resultSet, ":{$key}", $descriptor, -1, OCI_B_BLOB);
                        } else {
                            oci_bind_by_name($_resultSet, ":{$key}", $value);
                        }
                    }
                }

                if (!@oci_execute($_resultSet, OCI_NO_AUTO_COMMIT)) {
                    $e = oci_error($_resultSet);

                    $this->error = $e['message'];
                    \RDK\Basic::LogFile("[RDK][Oracle] Error execute SQL: {$e['message']}");
                    if ($e['sqltext'] == '') {
                        \RDK\Basic::LogFile($this->sql);
                    } else {
                        \RDK\Basic::LogFile($e['sqltext']);
                    }
                    \RDK\Basic::LogFile($this->bind);
                    oci_rollback($this->conn);
                } else {
                    if ($_lob != false) {
                        $descriptor->save($_lob);
                    }
                    $this->rowns = oci_num_rows($_resultSet);
                    if ($commit) {
                        oci_commit($this->conn);
                    }
                }

            } else {
                if ($commit) {
                    oci_commit($this->conn);
                }
                $_resultSet = true;
            }
        } else {
            \RDK\Basic::LogFile('[RDK][Oracle] Error open connection');
            $_resultSet = false;
        }
        return $_resultSet;
    }

}