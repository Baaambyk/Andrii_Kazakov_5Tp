<?php
/**
 * Paginacja — oczekuje $pagination z kluczami: page, pages.
 */
$page = (int) ($pagination['page'] ?? 1);
$pages = (int) ($pagination['pages'] ?? 1);

if ($pages <= 1) {
    return;
}

$from = max(1, $page - 2);
$to = min($pages, $page + 2);
?>
<nav class="mt-4" aria-label="Paginacja">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= e(query_with(['page' => max(1, $page - 1)])) ?>" aria-label="Poprzednia">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>

        <?php if ($from > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= e(query_with(['page' => 1])) ?>">1</a></li>
            <?php if ($from > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $from; $i <= $to; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>>
                <a class="page-link" href="?<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($to < $pages): ?>
            <?php if ($to < $pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= e(query_with(['page' => $pages])) ?>"><?= $pages ?></a></li>
        <?php endif; ?>

        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= e(query_with(['page' => min($pages, $page + 1)])) ?>" aria-label="Następna">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
