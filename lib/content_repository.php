<?php

function get_sections($db)
{
    $sql = "SELECT DISTINCT section FROM content_items ORDER BY section";
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    return $rows ? $rows : array();
}

function get_contents($db, $section = null)
{
    if ($section === null || $section === '') {
        $stmt = $db->query("SELECT * FROM content_items ORDER BY section, sort_order, id DESC");
        return $stmt->fetchAll();
    }

    $stmt = $db->prepare("SELECT * FROM content_items WHERE section = :section ORDER BY sort_order, id DESC");
    $stmt->execute(array('section' => $section));
    return $stmt->fetchAll();
}

function get_content($db, $id)
{
    $stmt = $db->prepare("SELECT * FROM content_items WHERE id = :id");
    $stmt->execute(array('id' => (int)$id));
    $row = $stmt->fetch();
    return $row ? $row : null;
}

function save_content($db, $data, $id = null)
{
    $payload = array(
        'section' => trim(isset($data['section']) ? (string)$data['section'] : 'general'),
        'title' => trim(isset($data['title']) ? (string)$data['title'] : ''),
        'subtitle' => trim(isset($data['subtitle']) ? (string)$data['subtitle'] : ''),
        'body' => trim(isset($data['body']) ? (string)$data['body'] : ''),
        'url' => trim(isset($data['url']) ? (string)$data['url'] : ''),
        'image_path' => trim(isset($data['image_path']) ? (string)$data['image_path'] : ''),
        'sort_order' => (int)(isset($data['sort_order']) ? $data['sort_order'] : 0),
        'is_active' => isset($data['is_active']) ? 1 : 0,
    );

    if ($id === null) {
        $stmt = $db->prepare(
            "INSERT INTO content_items
            (section, title, subtitle, body, url, image_path, sort_order, is_active, created_at, updated_at)
            VALUES
            (:section, :title, :subtitle, :body, :url, :image_path, :sort_order, :is_active, NOW(), NOW())"
        );
        $stmt->execute($payload);
        return;
    }

    $payload['id'] = (int)$id;
    $stmt = $db->prepare(
        "UPDATE content_items SET
            section = :section,
            title = :title,
            subtitle = :subtitle,
            body = :body,
            url = :url,
            image_path = :image_path,
            sort_order = :sort_order,
            is_active = :is_active,
            updated_at = NOW()
        WHERE id = :id"
    );
    $stmt->execute($payload);
}

function delete_content($db, $id)
{
    $stmt = $db->prepare("DELETE FROM content_items WHERE id = :id");
    $stmt->execute(array('id' => (int)$id));
}

function get_settings($db)
{
    $rows = $db->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key")->fetchAll();
    $settings = array();
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function upsert_setting($db, $key, $value)
{
    $stmt = $db->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:key, :value, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
    );
    $stmt->execute(array('key' => $key, 'value' => $value));
}
