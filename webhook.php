<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

require_once 'db.php';
require_once 'github.php';
require_once 'telegram.php';

$raw = file_get_contents("php://input");
$update = json_decode($raw, true);

file_put_contents("telegram.log", $raw.PHP_EOL, FILE_APPEND);

if (!isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = trim($message['text'] ?? '');

function generateCode()
{
    return 'TG-VERIFY-' . bin2hex(random_bytes(4));
}

if (str_starts_with($text, '/link ')) {

    $githubUser = trim(substr($text, 6));
    $code = generateCode();

    $stmt = $pdo->prepare(
        "INSERT INTO users
        (telegram_id, telegram_username, github_username, verification_code, verified)
        VALUES (?, ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE
        github_username=VALUES(github_username),
        verification_code=VALUES(verification_code),
        verified=0"
    );

    $stmt->execute([
        $message['from']['id'],
        $message['from']['username'] ?? '',
        $githubUser,
        $code
    ]);

    sendMessage(
        $chatId,
        "Put this text into your GitHub bio:\n\n".$code."\n\nThen run /verify"
    );

} elseif ($text === '/verify') {

    $stmt = $pdo->prepare("SELECT * FROM users WHERE telegram_id=?");
    $stmt->execute([$message['from']['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendMessage($chatId, "Run /link github_username first");
        exit;
    }

    $github = githubUser($user['github_username']);
    $bio = $github['bio'] ?? '';

    if (strpos($bio, $user['verification_code']) !== false) {

        $stmt = $pdo->prepare(
            "UPDATE users SET verified=1 WHERE telegram_id=?"
        );

        $stmt->execute([$message['from']['id']]);

        sendMessage($chatId, "Verification successful");

    } else {
        sendMessage($chatId, "Verification code not found in GitHub bio");
    }

} elseif ($text === '/myissues') {

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE telegram_id=? AND verified=1"
    );

    $stmt->execute([$message['from']['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendMessage($chatId, "Authenticate first with /link and /verify");
        exit;
    }

    $stmt = $pdo->query(
        "SELECT * FROM project_settings LIMIT 1"
    );
    
    $config =
        $stmt->fetch(PDO::FETCH_ASSOC);
    
    $issues = getMyProjectIssues(
        $user['github_username'],
        $config['project_id']
    );
    


    $msg = "Your Issues:\n\n";
    
    foreach (
        $issues['data']['node']['items']['nodes']
        as $item
    ) {
    
        $content =
            $item['content']
            ?? null;
    
        if (!$content) {
            continue;
        }
    
        $assigned = false;
    
        foreach (
            $content['assignees']['nodes']
            as $assignee
        ) {
    
            if (
                strtolower($assignee['login'])
                ===
                strtolower($user['github_username'])
            ) {
    
                $assigned = true;
                break;
            }
        }
    
        if (!$assigned) {
            continue;
        }
    
        $status = 'Unknown';
    
        foreach (
            $item['fieldValues']['nodes']
            as $fieldValue
        ) {
    
            if (
                ($fieldValue['field']['name'] ?? '')
                ===
                'Status'
            ) {
    
                $status =
                    $fieldValue['name'];
            }
        }
    
        $msg .=
            "#" .
            $content['number'] .
            " " .
            $content['title'] .
            "\n";
    
        $msg .=
            "Status: " .
            $status .
            "\n\n";
    }
    sendMessage($chatId,$msg);
}elseif (preg_match('/^\/done (\d+)$/', $text, $m)) {

    $issueNumber = (int)$m[1];

    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE telegram_id=?
         AND verified=1"
    );

    $stmt->execute([$message['from']['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendMessage($chatId, "Authenticate first");
        exit;
    }

    $stmt = $pdo->query(
        "SELECT * FROM project_settings LIMIT 1"
    );

    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        sendMessage(
            $chatId,
            "Run /projects first"
        );
        exit;
    }

    $issue = getIssueNodeId($issueNumber);

    $issueNodeId =
        $issue['data']['repository']['issue']['id']
        ?? null;

    if (!$issueNodeId) {

        sendMessage(
            $chatId,
            "Issue not found"
        );

        exit;
    }

    $itemId = getProjectItemId(
        $config['project_id'],
        $issueNodeId
    );

    if (!$itemId) {

        sendMessage(
            $chatId,
            "Issue not found in project"
        );

        exit;
    }

    $result = updateProjectStatus(
        $config['project_id'],
        $itemId,
        $config['status_field_id'],
        $config['done_option_id']
    );

    file_put_contents(
        'status.log',
        print_r($result, true),
        FILE_APPEND
    );

    if (!empty($result['errors'])) {

        sendMessage(
            $chatId,
            "GitHub Error:\n" .
            json_encode($result['errors'])
        );

        exit;
    }

    sendMessage(
        $chatId,
        "✅ Issue #{$issueNumber} moved to Done"
    );
}elseif (preg_match('/^\/start (\d+)$/', $text, $m)) {

    $issueNumber = (int)$m[1];

    $stmt = $pdo->query(
        "SELECT * FROM project_settings LIMIT 1"
    );

    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {

        sendMessage(
            $chatId,
            "Run /projects first"
        );

        exit;
    }

    $issue = getIssueNodeId($issueNumber);

    $issueNodeId =
        $issue['data']['repository']['issue']['id']
        ?? null;

    if (!$issueNodeId) {

        sendMessage(
            $chatId,
            "Issue not found"
        );

        exit;
    }

    $itemId = getProjectItemId(
        $config['project_id'],
        $issueNodeId
    );

    if (!$itemId) {

        sendMessage(
            $chatId,
            "Issue not found in project"
        );

        exit;
    }

    $result = updateProjectStatus(
        $config['project_id'],
        $itemId,
        $config['status_field_id'],
        $config['progress_option_id']
    );

    file_put_contents(
        'status.log',
        print_r($result, true),
        FILE_APPEND
    );

    if (!empty($result['errors'])) {

        sendMessage(
            $chatId,
            "GitHub Error:\n" .
            json_encode($result['errors'])
        );

        exit;
    }

    sendMessage(
        $chatId,
        "🚀 Issue #{$issueNumber} moved to In Progress"
    );
}elseif ($text == '/projects') {

    if (
        ($message['from']['username'] ?? '')
        !== 'Amirrezashahidi'
    ) {
        sendMessage(
            $chatId,
            'Access denied'
        );
        exit;
    }

    $projects = getProjects();

file_put_contents(
    'projects.log',
    print_r($projects, true),
    FILE_APPEND
);
    $project =
        $projects['data']['viewer']
        ['projectsV2']['nodes'][0]
        ?? null;

    if (!$project) {

        sendMessage(
            $chatId,
            'No project found'
        );

        exit;
    }

    $projectId = $project['id'];

    $fields =
        getProjectFields($projectId);

    $statusField = null;

    foreach (
        $fields['data']['node']['fields']['nodes']
        as $field
    ) {

        if (
            strtolower($field['name'])
            ===
            'status'
        ) {

            $statusField = $field;
            break;
        }
    }

    if (!$statusField) {

        sendMessage(
            $chatId,
            'Status field not found'
        );

        exit;
    }

    $todo = null;
    $progress = null;
    $review = null;
    $done = null;

    foreach (
        $statusField['options']
        as $option
    ) {

        switch (
            strtolower($option['name'])
        ) {

            case 'todo':
                $todo = $option['id'];
                break;

            case 'in progress':
                $progress = $option['id'];
                break;

            case 'review':
                $review = $option['id'];
                break;

            case 'done':
                $done = $option['id'];
                break;
        }
    }

    $pdo->exec(
        "TRUNCATE project_settings"
    );

    $stmt = $pdo->prepare(
        "INSERT INTO project_settings
        (
            project_id,
            status_field_id,
            todo_option_id,
            progress_option_id,
            review_option_id,
            done_option_id
        )
        VALUES (?,?,?,?,?,?)"
    );

    $stmt->execute([
        $projectId,
        $statusField['id'],
        $todo,
        $progress,
        $review,
        $done
    ]);

    sendMessage(
        $chatId,
        "✅ Project initialized"
    );
}elseif (
    preg_match(
        '/^\/issue (\d+)$/',
        $text,
        $m
    )
) {

    $issueNumber = (int)$m[1];

    $issue = getIssueDetails(
        $issueNumber
    );

    $data =
        $issue['data']['repository']['issue']
        ?? null;

    if (!$data) {

        sendMessage(
            $chatId,
            "Issue not found"
        );

        exit;
    }

    $stmt = $pdo->query(
        "SELECT * FROM project_settings LIMIT 1"
    );

    $config =
        $stmt->fetch(PDO::FETCH_ASSOC);

    $status =
        getIssueStatus(
            $config['project_id'],
            $data['id']
        );

    $assignees = [];

    foreach (
        $data['assignees']['nodes']
        as $assignee
    ) {

        $assignees[] =
            $assignee['login'];
    }

    $labels = [];

    foreach (
        $data['labels']['nodes']
        as $label
    ) {

        $labels[] =
            $label['name'];
    }

    $body =
        trim(
            strip_tags(
                $data['body'] ?? ''
            )
        );

    if (
        strlen($body) > 800
    ) {
        $body =
            substr(
                $body,
                0,
                800
            ) . "...";
    }

    $msg =
        "#".$data['number'].
        " ".$data['title']."\n\n";

    $msg .=
        "📊 Status: ".
        $status."\n";

    $msg .=
        "👤 Assignees: ".
        implode(
            ', ',
            $assignees
        )."\n";

    $msg .=
        "🏷 Labels: ".
        implode(
            ', ',
            $labels
        )."\n\n";

    $msg .=
        "📝 Description:\n".
        $body.
        "\n\n";

    $msg .=
        "🔗 ".
        $data['url'];

    sendMessage(
        $chatId,
        $msg
    );
}elseif ($text === '/guide') {

    $msg = " راهنمای ربات میوتمکن \n\n";

    $msg .= "🔗 احراز هویت\n";
    $msg .= "/link username\n";
    $msg .= "اتصال اکانت تلگرام به GitHub\n\n";

    $msg .= "/verify\n";
    $msg .= "تایید مالکیت اکانت GitHub\n\n";

    $msg .= "📋 مدیریت تسک‌ها\n";
    $msg .= "/myissues\n";
    $msg .= "نمایش تسک‌های تخصیص داده شده به شما\n\n";

    $msg .= "/task شماره\n";
    $msg .= "نمایش جزئیات کامل یک تسک\n";
    $msg .= "مثال: /task 42\n\n";

    $msg .= "/start شماره\n";
    $msg .= "تغییر وضعیت تسک به In Progress\n";
    $msg .= "مثال: /start 42\n\n";

    $msg .= "/done شماره\n";
    $msg .= "تغییر وضعیت تسک به Done\n";
    $msg .= "مثال: /done 42\n\n";

    $msg .= "⚙️ دستورات مدیریتی\n";
    $msg .= "/projects\n";
    $msg .= "همگام‌سازی پروژه GitHub و تنظیمات Status\n\n";

    $msg .= "💡 نمونه استفاده\n";
    $msg .= "/myissues\n";
    $msg .= "/task 15\n";
    $msg .= "/start 15\n";
    $msg .= "/done 15\n";

    sendMessage($chatId, $msg);
}