<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Campaigns for <?= $adsAccount['name'] ?> (<?= $adsAccount['customer_id'] ?>)</h4>
            <a href="<?= base_url('/google/ads/accounts') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Accounts
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($campaigns)): ?>
            <div class="alert alert-info">
                <p>No campaigns found for this account.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="campaigns-table">
                    <thead>
                        <tr>
                            <th scope="col">Campaign Name</th>
                            <th scope="col">ID</th>
                            <th scope="col">Status</th>
                            <th scope="col">Budget</th>
                            <th scope="col">Cost</th>
                            <th scope="col">Conv. Value/Cost</th>
                            <th scope="col">Conversions</th>
                            <th scope="col">Cost/Conv.</th>
                            <th scope="col">Conv. Rate</th>
                            <th scope="col">Target CPA</th>
                            <th scope="col">Target ROAS</th>
                            <th scope="col">CTR</th>
                            <th scope="col">Clicks</th>
                            <th scope="col">Avg. CPC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><?= $campaign['name'] ?></td>
                                <td><?= $campaign['id'] ?></td>
                                <td>
                                    <span class="badge <?= $campaign['status'] === 'ENABLED' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $campaign['status'] ?>
                                    </span>
                                </td>
                                <td><?= number_format($campaign['budget_id'], 2) ?></td>
                                <td><?= number_format($campaign['cost'], 2) ?></td>
                                <td><?= number_format($campaign['roas'], 2) ?></td>
                                <td><?= number_format($campaign['conversions'], 2) ?></td>
                                <td><?= number_format($campaign['cost_per_conversion'], 2) ?></td>
                                <td><?= number_format($campaign['conversion_rate'], 2) ?>%</td>
                                <td><?= $campaign['target_cpa'] ? number_format($campaign['target_cpa'], 2) : 'N/A' ?></td>
                                <td><?= $campaign['target_roas'] ? number_format($campaign['target_roas'], 2) : 'N/A' ?></td>
                                <td><?= number_format($campaign['ctr'], 2) ?>%</td>
                                <td><?= number_format($campaign['clicks']) ?></td>
                                <td><?= number_format($campaign['average_cpc'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>
<link href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<script>
$(document).ready(function() {
    $('#campaigns-table').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        responsive: true
    });
});
</script>
<?= $this->endSection() ?>