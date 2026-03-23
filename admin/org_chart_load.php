<?php
ob_start();

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'), JSON_UNESCAPED_UNICODE);
    exit;
}

require_admin_login();

function json_response($success, $message, $extra = array(), $status = 200)
{
    if (!headers_sent()) {
        http_response_code((int)$status);
    }

    $payload = array_merge(array(
        'success' => (bool)$success,
        'message' => (string)$message,
    ), $extra);

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function cast_row_numeric($row, $intFields)
{
    if (!is_array($row)) {
        return $row;
    }

    foreach ($intFields as $f) {
        if (array_key_exists($f, $row) && $row[$f] !== null) {
            $row[$f] = (int)$row[$f];
        }
    }

    return $row;
}

try {
    $stmtMain = $db->prepare('SELECT * FROM org_charts WHERE chart_type = :chart_type AND status = 1 ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmtMain->execute(array('chart_type' => 'main'));
    $mainChart = $stmtMain->fetch(PDO::FETCH_ASSOC);

    if (!$mainChart) {
        json_response(true, 'OK', array(
            'empty_state' => true,
            'data' => array(
                'main_chart' => null,
                'main_nodes' => array(),
                'main_connections' => array(),
                'department_charts' => array(),
                'department_nodes' => array(),
                'department_connections' => array(),
            ),
        ));
    }

    $mainChart = cast_row_numeric($mainChart, array('id', 'parent_chart_id', 'main_source_node_id', 'root_node_id', 'sort_order', 'status'));
    $mainChartId = (int)$mainChart['id'];

    $stmtNodes = $db->prepare('SELECT * FROM org_chart_nodes WHERE chart_id = :chart_id AND status = 1 ORDER BY level_no ASC, sort_order ASC, id ASC');
    $stmtNodes->execute(array('chart_id' => $mainChartId));
    $mainNodes = $stmtNodes->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mainNodes as $k => $node) {
        $mainNodes[$k] = cast_row_numeric($node, array('id', 'chart_id', 'parent_node_id', 'x_position', 'y_position', 'sort_order', 'level_no', 'status'));
    }

    $stmtConnections = $db->prepare('SELECT * FROM org_chart_connections WHERE chart_id = :chart_id ORDER BY id ASC');
    $stmtConnections->execute(array('chart_id' => $mainChartId));
    $mainConnections = $stmtConnections->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mainConnections as $k => $conn) {
        $mainConnections[$k] = cast_row_numeric($conn, array('id', 'chart_id', 'source_node_id', 'target_node_id'));
    }

    $stmtDeptCharts = $db->prepare('SELECT * FROM org_charts WHERE chart_type = :chart_type AND parent_chart_id = :parent_chart_id AND status = 1 ORDER BY sort_order ASC, id ASC');
    $stmtDeptCharts->execute(array(
        'chart_type' => 'department',
        'parent_chart_id' => $mainChartId,
    ));

    $deptChartsRaw = $stmtDeptCharts->fetchAll(PDO::FETCH_ASSOC);
    $departmentCharts = array();
    $departmentNodes = array();
    $departmentConnections = array();

    foreach ($deptChartsRaw as $chart) {
        $chart = cast_row_numeric($chart, array('id', 'parent_chart_id', 'main_source_node_id', 'root_node_id', 'sort_order', 'status'));
        $chartId = (int)$chart['id'];

        $stmtNodes->execute(array('chart_id' => $chartId));
        $nodes = $stmtNodes->fetchAll(PDO::FETCH_ASSOC);
        foreach ($nodes as $i => $node) {
            $nodes[$i] = cast_row_numeric($node, array('id', 'chart_id', 'parent_node_id', 'x_position', 'y_position', 'sort_order', 'level_no', 'status'));
        }

        $stmtConnections->execute(array('chart_id' => $chartId));
        $connections = $stmtConnections->fetchAll(PDO::FETCH_ASSOC);
        foreach ($connections as $j => $conn) {
            $connections[$j] = cast_row_numeric($conn, array('id', 'chart_id', 'source_node_id', 'target_node_id'));
        }

        $departmentCharts[] = array(
            'chart' => $chart,
            'nodes' => $nodes,
            'connections' => $connections,
        );

        $departmentNodes[$chartId] = $nodes;
        $departmentConnections[$chartId] = $connections;
    }

    json_response(true, 'OK', array(
        'empty_state' => false,
        'data' => array(
            'main_chart' => $mainChart,
            'main_nodes' => $mainNodes,
            'main_connections' => $mainConnections,
            'department_charts' => $departmentCharts,
            'department_nodes' => $departmentNodes,
            'department_connections' => $departmentConnections,
        ),
    ));
} catch (Exception $e) {
    json_response(false, 'Unable to load organization charts', array(), 500);
}
