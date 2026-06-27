<?php
session_start();
include "php/db.php";

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Member name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($national_id)) $errors[] = "National ID is required";
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    } else {
        if ($id == 0) {
            // Check if national ID already exists
            $check = $conn->prepare("SELECT id FROM members WHERE national_id = ?");
            $check->bind_param("s", $national_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $_SESSION['error'] = "Member with this national ID already exists";
            } else {
                $stmt = $conn->prepare("INSERT INTO members (name, phone, national_id, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $phone, $national_id, $email, $address, $status);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Member added successfully!";
                    $action = 'list';
                } else {
                    $_SESSION['error'] = "Error adding member";
                }
                $stmt->close();
            }
            $check->close();
        } else {
            $stmt = $conn->prepare("UPDATE members SET name = ?, phone = ?, national_id = ?, email = ?, address = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $name, $phone, $national_id, $email, $address, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Member updated successfully!";
                $action = 'list';
            } else {
                $_SESSION['error'] = "Error updating member";
            }
            $stmt->close();
        }
    }
    
    header("Location: members.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Member deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting member";
    }
    $stmt->close();
    header("Location: members.php");
    exit();
}

// Fetch member for editing
$edit_member = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all members
$result = $conn->query("SELECT * FROM members ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - COFISEE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        nav {
            background: #f8f9fa;
            padding: 0;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }
        nav a {
            padding: 12px 18px;
            text-decoration: none;
            color: #333;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-size: 0.95em;
        }
        nav a:hover {
            background: #e9ecef;
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        .main-content {
            padding: 40px;
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .form-section, .table-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"],
        input[type="email"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }
        button:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table thead {
            background: #667eea;
            color: white;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
        }
        table tbody tr:hover {
            background: #f0f0f0;
        }
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            font-size: 0.85em;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background: #007bff;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            opacity: 0.8;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Members Management</h1>
        </header>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="members.php">Members</a>
            <a href="loans.php">Loans</a>
            <a href="repayments.php">Repayments</a>
            <a href="defaulters.php">Defaulters</a>
            <a href="expenses.php">Expenses</a>
            <a href="savings.php">Savings</a>
            <a href="logout.php">Logout</a>
        </nav>

        <div class="main-content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="form-section">
                    <h2><?= $action === 'add' ? 'Add New Member' : 'Edit Member'; ?></h2>
                    <form method="POST">
                        <?php if ($action === 'edit' && $edit_member): ?>
                            <input type="hidden" name="id" value="<?= $edit_member['id']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="id" value="0">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required value="<?= $edit_member['name'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="text" name="phone" required value="<?= $edit_member['phone'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>National ID *</label>
                                <input type="text" name="national_id" required value="<?= $edit_member['national_id'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= $edit_member['email'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" value="<?= $edit_member['address'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="active" <?= ($edit_member['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?= ($edit_member['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?= ($edit_member['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit"><?= $action === 'add' ? 'Add Member' : 'Update Member'; ?></button>
                        <a href="members.php"><button type="button" class="btn-secondary">Cancel</button></a>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-section">
                <h2>Members List</h2>
                <?php if ($result && $result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>National ID</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id']; ?></td>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= htmlspecialchars($row['phone']); ?></td>
                                    <td><?= htmlspecialchars($row['national_id']); ?></td>
                                    <td><?= htmlspecialchars($row['email'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="members.php?action=edit&id=<?= $row['id']; ?>" class="btn-edit">Edit</a>
                                            <a href="members.php?delete=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this member?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No members found. <a href="members.php?action=add">Add your first member</a></p>
                <?php endif; ?>
            </div>

            <?php if (!($action === 'add' || $action === 'edit')): ?>
                <div style="margin-top: 20px;">
                    <a href="members.php?action=add"><button>+ Add New Member</button></a>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>&copy; 2026 COFISEE Microfinance System. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>
