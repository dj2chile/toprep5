<?php
if (!isset($repairId) || !isset($STATUS_COLORS)) {
    throw new Exception('Missing required variables for status dropdown');
}
?>

<div id="status-dropdown-<?= $repairId ?>" class="status-dropdown">
    <form class="status-change-form" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="repair_id" value="<?= $repairId ?>">
        <input type="hidden" name="update_status" value="1">
        
        <?php foreach($STATUS_COLORS as $status => $colorClasses): ?>
            <?php if($status !== $currentStatus): ?>
                <button type="submit"
                        name="new_status"
                        value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        onclick="return confirm('Czy na pewno chcesz zmienić status na <?= addslashes($status) ?>?')">
                    <span class="inline-flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?= $colorClasses ?>"></span>
                        <?= htmlspecialchars($status) ?>
                    </span>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>

<div id="status-dropdown-mobile-<?= $repairId ?>" class="status-dropdown">
    <form class="status-change-form" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="repair_id" value="<?= $repairId ?>">
        <input type="hidden" name="update_status" value="1">
        
        <?php foreach($STATUS_COLORS as $status => $colorClasses): ?>
            <?php if($status !== $currentStatus): ?>
                <button type="submit"
                        name="new_status"
                        value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        onclick="return confirm('Czy na pewno chcesz zmienić status na <?= addslashes($status) ?>?')">
                    <span class="inline-flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full <?= $colorClasses ?>"></span>
                        <?= htmlspecialchars($status) ?>
                    </span>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </form>
</div>