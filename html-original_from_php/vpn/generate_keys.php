<?php
try {
    // Генериране на частен ключ
    $privateKey = trim(shell_exec("wg genkey 2>&1"));
    if (!$privateKey) {
        throw new Exception("Failed to generate private key. Please ensure WireGuard is installed and accessible.");
    }

    // Генериране на публичен ключ от частния
    $publicKey = trim(shell_exec("echo $privateKey | wg pubkey 2>&1"));
    if (!$publicKey) {
        throw new Exception("Failed to generate public key. Ensure WireGuard commands work as expected.");
    }

    // Връщане на ключовете като JSON
    echo json_encode(['privateKey' => $privateKey, 'publicKey' => $publicKey]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>

