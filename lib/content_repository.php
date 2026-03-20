<?php
declare(strict_types=1);

function get_sections(PDO $db): array
{
    $sql = "SELECT DISTINCT section FROM content_items ORDER BY section";
    return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function get_contents(PDO $db, ?string $section = null): array
{
    if ($section === null || $section === '') {
        $stmt = $db->query("SELECT * FROM content_items ORDER BY section, sort_order, id DESC");
        return $stmt->fetchAll();
    }

    $stmt = $db->prepare("SELECT * FROM content_items WHERE section = :section ORDER BY sort_order, id DESC");
    $stmt->execute(['section' => $section]);
    return $stmt->fetchAll();
}

function get_content(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT * FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function save_content(PDO $db, array $data, ?int $id = null): void
{
    $payload = [
        'section' => trim((string) ($data['section'] ?? 'general')),
        'title' => trim((string) ($data['title'] ?? '')),
        'subtitle' => trim((string) ($data['subtitle'] ?? '')),
        'body' => trim((string) ($data['body'] ?? '')),
        'url' => trim((string) ($data['url'] ?? '')),
        'image_path' => trim((string) ($data['image_path'] ?? '')),
        'sort_order' => (int) ($data['sort_order'] ?? 0),
        'is_active' => isset($data['is_active']) ? 1 : 0,
    ];

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

    $payload['id'] = $id;
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

function delete_content(PDO $db, int $id): void
{
    $stmt = $db->prepare("DELETE FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function get_settings(PDO $db): array
{
    $rows = $db->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key")->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function upsert_setting(PDO $db, string $key, string $value): void
{
    $stmt = $db->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:key, :value, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
    );
    $stmt->execute(['key' => $key, 'value' => $value]);
}

