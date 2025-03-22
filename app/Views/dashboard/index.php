<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Dashboard</h4>
        <a href="<?= base_url('/google/connect') ?>" class="btn btn-primary">
            <i class="bi bi-google"></i> Connect Google Account
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($google_accounts)): ?>
            <div class="alert alert-info">
                <p>Connect your Google account to manage your Google Ads campaigns.</p>
            </div>
        <?php else: ?>
            <h5>Connected Google Accounts</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Account Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($google_accounts as $account): ?>
                            <tr>
                                <td><?= $account['name'] ?></td>
                                <td><?= $account['email'] ?></td>
                                <td>
                                    <?php
                                    $expiry = new DateTime($account['token_expiry']);
                                    $now = new DateTime();
                                    if ($expiry > $now) {
                                        echo '<span class="badge bg-success">Active</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('/google/ads/accounts/' . $account['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-list"></i> View Ads Accounts
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>