<style>
/* ── Inline Role Dropdown ─────────────────────────────────── */
.role-select-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.role-select {
    -webkit-appearance: none;
    appearance: none;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #f9fafb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%236b7280' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 8px center;
    padding: 4px 28px 4px 10px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s, background-color .15s;
    min-width: 130px;
    line-height: 1.4;
}

.role-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.18);
    background-color: #fff;
}

.role-select:hover {
    border-color: #9ca3af;
    background-color: #fff;
}

/* Status badge next to dropdown */
.role-save-status {
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 500;
    transition: opacity .3s;
}

.role-save-status.saving  { color: #6b7280; }
.role-save-status.saved   { color: #16a34a; }
.role-save-status.error   { color: #dc2626; }
.role-save-status.warning { color: #d97706; }

/* Spinning icon */
@keyframes spin { to { transform: rotate(360deg); } }
.spin-icon { display: inline-block; animation: spin 1s linear infinite; }

/* Row highlight on save success */
tr.save-flash td {
    animation: flash-green .6s ease;
}
@keyframes flash-green {
    0%   { background-color: #dcfce7; }
    100% { background-color: transparent; }
}
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">List of users</h3>
            </div>
            <div class="panel-body">
                <p style="display: flex; gap: 10px; align-items: center;">
                    <a href="user/add_user" class="btn btn-info link"><i class="fa fa-plus"></i> Add new user</a>
                    <select id="role-filter" class="form-control" style="width: 200px;">
                        <option value="">-- Tampilkan Semua Role --</option>
                        <option value="client picker">Hanya Client Picker</option>
                        <option value="client packer">Hanya Client Packer</option>
                    </select>
                </p>
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role</th>
                            <th class="text-center">Photo</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Last login</th>
                            <th>Created</th>
                            <th class="text-center">Bypass?</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($list_user as $user): ?>
                        <tr id="user-row-<?= $user['id_user'] ?>">
                            <td><?= $i++; ?></td>
                            <td style="min-width:170px" data-search="<?= htmlspecialchars($user['akses'] ?? '') ?>">
                                <div class="role-select-wrap">
                                    <select
                                        class="role-select"
                                        id="role-sel-<?= $user['id_user'] ?>"
                                        data-id="<?= $user['id_user'] ?>"
                                        data-original="<?= $user['hakakses'] ?>"
                                        onchange="saveRole(this)"
                                        title="Klik untuk ubah role"
                                    >
                                        <?php foreach ($list_role as $role): ?>
                                        <option value="<?= $role['id_hakakses'] ?>"
                                            <?= ($user['hakakses'] == $role['id_hakakses']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['akses']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="role-save-status" id="role-status-<?= $user['id_user'] ?>"></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $foto_url = base_url('assets/img/no-image.jpg');
                                    if (!empty($user['foto'])) {
                                        $foto_url = (strpos($user['foto'], 'http') === 0) ? $user['foto'] : base_url($user['foto']);
                                    }
                                ?>
                                <a href="<?= $foto_url ?>" class="gallery-item" title="User: <?= $user['username'] ?>" data-gallery>
                                    <img src="<?= $foto_url ?>" 
                                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;" 
                                         alt="Profile"
                                         onerror="this.src='<?= base_url('assets/img/no-image.jpg') ?>'">
                                </a>
                            </td>
                            <td><?= $user['username'] ?></td>
                            <td><?= $user['name'] ?></td>
                            <td><?= $user['lastlogin'] ?></td>
                            <td><?= $user['created'] ?></td>
                            <td class="text-center"><?= $user['bypass'] ? '<i class="fa fa-check"></i>' : null ?></td>
                            <td class="text-center">
                                <a href="user/edit_user/<?= $user['id_user'] ?>" class="btn btn-success link"><i class="fa fa-edit"></i> </a>
                                <a href="user/generate_password_user/<?= $user['id_user'] ?>" class="btn btn-default link"><i class="fa fa-gears"></i> </a>
                                <a href="user/delete_user/<?= $user['id_user'] ?>" class="btn btn-danger confirm" onClick="notyConfirm(event);"><i class="fa fa-trash-o"></i> </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * saveRole – called when the role <select> changes.
 * POSTs to user/update_role_user via AJAX and shows inline feedback.
 */
function saveRole(sel) {
    var userId   = sel.dataset.id;
    var hakakses = sel.value;
    var statusEl = document.getElementById('role-status-' + userId);
    var row      = document.getElementById('user-row-' + userId);

    // Show saving spinner
    statusEl.className  = 'role-save-status saving';
    statusEl.innerHTML  = '<span class="spin-icon"><i class="fa fa-spinner"></i></span>';
    sel.disabled        = true;

    $.ajax({
        url    : '<?= base_url('user/update_role_user') ?>',
        method : 'POST',
        data   : { id_user: userId, hakakses: hakakses },
        dataType: 'json',
        success: function (res) {
            sel.disabled = false;

            if (res.status === 'success') {
                statusEl.className = 'role-save-status saved';
                statusEl.innerHTML = '<i class="fa fa-check-circle"></i>';
                sel.dataset.original = hakakses;

                // Flash the row green briefly
                row.classList.add('save-flash');
                setTimeout(function () { row.classList.remove('save-flash'); }, 700);

            } else if (res.status === 'warning') {
                statusEl.className = 'role-save-status warning';
                statusEl.innerHTML = '<i class="fa fa-minus-circle" title="' + (res.message || 'Tidak ada perubahan') + '"></i>';

            } else {
                statusEl.className = 'role-save-status error';
                statusEl.innerHTML = '<i class="fa fa-times-circle" title="' + (res.message || 'Gagal menyimpan') + '"></i>';
                // Revert
                sel.value = sel.dataset.original;
            }

            // Auto-clear status icon after 2.5 s
            setTimeout(function () {
                statusEl.innerHTML  = '';
                statusEl.className  = 'role-save-status';
            }, 2500);
        },
        error: function () {
            sel.disabled       = false;
            sel.value          = sel.dataset.original;
            statusEl.className = 'role-save-status error';
            statusEl.innerHTML = '<i class="fa fa-times-circle" title="Gagal – coba lagi"></i>';
            setTimeout(function () {
                statusEl.innerHTML = '';
                statusEl.className = 'role-save-status';
            }, 3000);
        }
    });
}

$(document).ready(function() {
    // Apply custom role filter using DataTables API
    var table = $('.datatable').DataTable();
    $('#role-filter').on('change', function() {
        var val = $(this).val();
        table.column(1).search(val).draw();
    });
});
</script>
