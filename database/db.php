<?php
require('connect.php');

function currentTime()
{
    $datetime = new DateTime();
    $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $datetime->setTimezone($timezone);
    $time = $datetime->format('Y-m-d H:i:s');
    // $time = date("Y-m-d H:i:s", time());
    // $time = strtotime(currentTime());
    return $time;
}
function dd($value)
{
    echo json_encode($value);
}

function showError($code, $error)
{
    http_response_code($code);
    $res['status'] = 0;
    $res['errors'] = $error;
    dd($res);
}

function executeQuerry($sql, $conditions = [])
{
    try {
        global $conn;
        $stmt = $conn->prepare($sql);
        $value = array_values($conditions);
        $stmt->execute($value);
        return $stmt;
    } catch (PDOException $e) {
        showError(400, $e->getMessage());
        exit();
    }
}
function selectAll($table, $conditions = [], $order = "")
{
    try {
        global $conn;
        $sql = "SELECT * FROM `$table`";
        if (empty($conditions)) {
            $sql = $sql . $order;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            $i = 0;
            foreach ($conditions as $key => $value) {
                if ($i === 0) {
                    $sql = $sql . " WHERE $key like '%$value%'";
                } else {
                    $sql = $sql . " AND $key like '%$value%'";
                }
                $i++;
            }
            $sql = $sql . $order;
            $stmt = executeQuerry($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}
function Search($table, $conditions = [], $order = "")
{
    try {
        global $conn;
        $sql = "SELECT * FROM `$table`";
        if (empty($conditions)) {
            $sql = $sql . $order;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            $i = 0;
            foreach ($conditions as $key => $value) {
                if ($i === 0) {
                    $sql = $sql . " WHERE $key like '%$value%'";
                } else {
                    $sql = $sql . " AND $key like '%$value%'";
                }
                $i++;
            }
            $sql = $sql . $order;
            $stmt = executeQuerry($sql, $conditions);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}
function selectOne($table, $conditions)
{
    try {
        $sql = "SELECT *   FROM `$table`";
        $i = 0;
        foreach ($conditions as $key => $value) {
            if ($i === 0) {
                $sql = $sql . " WHERE $key=?";
            } else {
                $sql = $sql . " AND $key=?";
            }
            $i++;
        }
        $sql = $sql . ' LIMIT 1';
        $stmt = executeQuerry($sql, $conditions);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        } else {
            return $result[0];
        }
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}
function create($table, $conditions)
{
    try {
        global $conn;
        $col = "(";
        $val = "(";
        $i = 0;
        foreach ($conditions as $column => $value) {

            if ($i === 0) {
                $col = $col . " $column";
                $val = $val . " ?";
            } else {
                $col = $col  . " ,$column";
                $val = $val . " ,?";
            }
            $i++;
        }
        $col = $col . " )";
        $val = $val . " )";
        $sql = "INSERT INTO `$table` $col VALUES $val ";
        $stmt = executeQuerry($sql, $conditions);

        $last_id = $conn->lastInsertId();
        return $last_id;
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}
function update($table, $where, $conditions)
{
    try {
        $sql = "UPDATE `$table` SET ";
        $i = 0;
        foreach ($conditions as $key => $val) {
            if ($i === 0) {
                $sql = $sql . " $key=?";
            } else {
                $sql = $sql . ", $key=?";
            }
            $i++;
        }

        foreach ($where as $key => $val) {
            $sql = $sql . " WHERE $key = ?";
        }
        $conditions = $conditions + $where;
        $stmt = executeQuerry($sql, $conditions);

        return $stmt->rowCount();
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}

function delete($table, $conditions)
{
    try {
        $sql = "DELETE FROM `$table`";
        $i = 0;
        foreach ($conditions as $key => $value) {
            if ($i === 0) {
                $sql = $sql . " WHERE $key=?";
            } else {
                $sql = $sql . " AND $key=?";
            }
            $i++;
        }
        $stmt = executeQuerry($sql, $conditions);

        return $stmt->rowCount();
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}
function custom($sql)
{
    try {
        global $conn;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    } catch (Error) {
        showError(400, "Errors: input value invalid");
        exit;
    }
}