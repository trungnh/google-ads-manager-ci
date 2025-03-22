<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Google Ads Accounts</h4>
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
            <?php foreach ($google_accounts as $account): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                        <h5><?= $account['name'] ?> (<?= $account['email'] ?>)</h5>
                        <a href="<?= base_url('/google/ads/sync/' . $account['id']) ?>" class="btn btn-success">
                            <i class="bi bi-arrow-repeat"></i> Sync Ads Accounts
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        $googleAdsAccountModel = new \App\Models\GoogleAdsAccountModel();
                        $adsAccounts = $googleAdsAccountModel->getAccountsByGoogleAccountId($account['id']);
                        ?>

                        <?php if (empty($adsAccounts)): ?>
                            <div class="alert alert-info">
                                <p>No Google Ads accounts found. Click "Sync Ads Accounts" to fetch your accounts.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Customer ID</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($adsAccounts as $adsAccount): ?>
                                            <tr>
                                                <td><?= $adsAccount['name'] ?></td>
                                                <td><?= $adsAccount['customer_id'] ?></td>
                                                <td>
                                                    <a href="<?= base_url('/google/ads/campaigns/' . $adsAccount['id']) ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-list"></i> View Campaigns
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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>