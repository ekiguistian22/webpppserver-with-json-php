<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "timeout"]);
    exit;
}

require_once 'routeros_api.class.php';

// KONFIGURASI MikroTik
$MT_HOST = "ip-public-mikrotik/vpn";
$MT_USER = "user-mikrotik";
$MT_PASS = "password-mikrotik";

// File JSON untuk simpan profile lama
$JSON_FILE = __DIR__ . "/isolir.json";
function load_json($file){ return file_exists($file) ? json_decode(file_get_contents($file), true) : []; }
function save_json($file, $data){ file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)); }

// File JSON untuk simpan last seen (manual isolir/kick)
$LAST_FILE = __DIR__ . "/last_seen.json";
function load_last($file){ return file_exists($file) ? json_decode(file_get_contents($file), true) : []; }
function save_last($file, $data){ file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)); }

$API = new RouterosAPI();

// ====== HANDLE ACTION (isolir / buka / kick) ======
if (isset($_POST['action'], $_POST['user'])) {
    $username = $_POST['user'];
    $db = load_json($JSON_FILE);
    $last = load_last($LAST_FILE);

    if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
        $secrets = $API->comm("/ppp/secret/print", ["?name" => $username]);
        if (!empty($secrets[0]['.id'])) {
            $id = $secrets[0]['.id'];
            $currentProfile = $secrets[0]['profile'] ?? '';

            if ($_POST['action'] === "isolir") {
                if (!isset($db[$username])) {
                    $db[$username] = $currentProfile;
                    save_json($JSON_FILE, $db);
                }
                $API->comm("/ppp/secret/set", [".id" => $id, "profile" => "EXPIRED"]);
                $active = $API->comm("/ppp/active/print", ["?name" => $username]);
                if (!empty($active[0]['.id'])) {
                    $API->comm("/ppp/active/remove", [".id" => $active[0]['.id']]);
                    // catat last seen manual
                    $last[$username] = date("Y-m-d H:i:s");
                    save_last($LAST_FILE, $last);
                }
                $API->disconnect();
                exit("ISOLIR_OK");
            }
            elseif ($_POST['action'] === "buka") {
                $restoreProfile = $db[$username] ?? 'REGULER';
                unset($db[$username]);
                save_json($JSON_FILE, $db);
                $API->comm("/ppp/secret/set", [".id" => $id, "profile" => $restoreProfile]);
                $API->disconnect();
                exit("BUKA_OK:$restoreProfile");
            }
            elseif ($_POST['action'] === "kick") {
                $active = $API->comm("/ppp/active/print", ["?name" => $username]);
                if (!empty($active[0]['.id'])) {
                    $API->comm("/ppp/active/remove", [".id" => $active[0]['.id']]);
                    // catat last seen manual
                    $last[$username] = date("Y-m-d H:i:s");
                    save_last($LAST_FILE, $last);
                }
                $API->disconnect();
                exit("KICK_OK");
            }
        }
        $API->disconnect();
    }
    exit("FAIL");
}

// ====== AMBIL DATA ======
$result = ["data" => [], "totalActive" => 0];
$db   = load_json($JSON_FILE);
$last = load_last($LAST_FILE);

if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
    $secrets = $API->comm("/ppp/secret/print");
    $activeUsers = $API->comm("/ppp/active/print");
    $result["totalActive"] = count($activeUsers);

    $activeMap = [];
    foreach ($activeUsers as $act) {
        if (isset($act['name'], $act['address'])) {
            $activeMap[$act['name']] = $act['address'];
        }
    }

    foreach ($secrets as $s) {
        $username   = $s['name'] ?? '';
        $profile    = $s['profile'] ?? '';
        $ipAddress  = $activeMap[$username] ?? '-';
        $isOnline   = $ipAddress !== '-' && filter_var($ipAddress, FILTER_VALIDATE_IP);
        $isIsolir   = isset($db[$username]) && $profile === "EXPIRED";

        // ambil last-logged-out dari mikrotik (secret)
        $lastLoggedOut = $s['last-logged-out'] ?? '';
        if ($lastLoggedOut !== '') {
            // format ulang: dari "aug/17/2025 01:12:30" → "2025-08-17 01:12:30"
            $lastLoggedOut = date("Y-m-d H:i:s", strtotime($lastLoggedOut));
        }

        // tentukan nilai last_seen
        if ($isOnline) {
            $lastSeen = date("Y-m-d H:i:s"); // sekarang, biar realtime login
        } else {
            // pakai last-logged-out dari Mikrotik, fallback ke JSON
            $lastSeen = $lastLoggedOut !== '' ? $lastLoggedOut : ($last[$username] ?? "-");
        }

        $result["data"][] = [
            "username"   => $username,
            "profile"    => $profile,
            "status"     => $isOnline ? "online" : "offline",
            "ip"         => $ipAddress,
            "comment"    => $s['comment'] ?? '',
            "isIsolir"   => $isIsolir,
            "last_seen"  => $lastSeen
        ];
    }

    $API->disconnect();
}

header("Content-Type: application/json");
echo json_encode($result);
