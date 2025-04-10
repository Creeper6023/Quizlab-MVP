<?php
require_once __DIR__ . '/../../config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();

// Get all users
$users = $db->getAllUsers();

// Get the page title
$pageTitle = "User Management";
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-users text-primary me-2"></i><?php echo $pageTitle; ?></h1>
        <div>
            <a href="add_user.php" class="btn btn-success me-2">
                <i class="fas fa-user-plus me-1"></i> Add User
            </a>
            <a href="import_users.php" class="btn btn-primary">
                <i class="fas fa-file-import me-1"></i> Import Users
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Users</h5>
                </div>
                <div class="col-auto">
                    <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search users..." onkeyup="filterUsers()">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Username</th>
                            <th scope="col">Role</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                ($user['role'] === 'teacher' ? 'success' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled>
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <p>No users found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal(document.getElementById('deleteModal'))" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-4">Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>This action cannot be undone.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('deleteModal'))">Cancel</button>
                <form action="<?= BASE_URL ?>/admin/users/delete_user.php" method="post" id="deleteForm">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple, reliable modal handling
    function confirmDelete(userId, username) {
        // Set the values in the form 
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserName').textContent = username;
        
        // Show the modal
        const deleteModal = document.getElementById('deleteModal');
        deleteModal.classList.add('show');
        document.body.classList.add('modal-open');
    }
    
    // Clean function to close any modal
    function closeModal(modalElement) {
        modalElement.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
    
    // Initialize tooltips and popovers if needed
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Bootstrap components
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Add event listeners for all modal close buttons
        document.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]').forEach(button => {
            button.addEventListener('click', function() {
                const modalElement = this.closest('.modal');
                closeModal(modalElement);
            });
        });
    });
    
    // Filter users function
    function filterUsers() {
        const input = document.getElementById('userSearch');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('usersTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const username = rows[i].getElementsByTagName('td')[1];
            const role = rows[i].getElementsByTagName('td')[2];
            
            if (username || role) {
                const usernameValue = username.textContent || username.innerText;
                const roleValue = role.textContent || role.innerText;
                
                if (usernameValue.toUpperCase().indexOf(filter) > -1 || 
                    roleValue.toUpperCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    }
</script>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>