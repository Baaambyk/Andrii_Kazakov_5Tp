<?php
/**
 * Karta sali — oczekuje zmiennej $room.
 */
$amenities = array_filter(array_map('trim', explode(',', (string) ($room['equipment'] ?? ''))));
?>
<article class="room-card h-100">
    <a class="room-media" href="<?= e(url('room.php?id=' . (int) $room['id'])) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(room_image($room)) ?>" alt="<?= e($room['name']) ?>" loading="lazy">
        <span class="price-tag"><?= e(money($room['price_per_hour'])) ?> <small>/ godz.</small></span>
        <?php if ((int) $room['capacity'] >= 20): ?>
            <span class="room-flag">Duża sala</span>
        <?php endif; ?>
    </a>
    <div class="room-body">
        <h3 class="room-title">
            <a href="<?= e(url('room.php?id=' . (int) $room['id'])) ?>"><?= e($room['name']) ?></a>
        </h3>
        <p class="room-location">
            <i class="bi bi-geo-alt me-1"></i><?= e($room['location']) ?>
        </p>
        <p class="room-desc"><?= e(excerpt((string) $room['description'], 92)) ?></p>
        <?php if ($amenities !== []): ?>
            <ul class="room-amenities">
                <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                    <li><?= e($amenity) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="room-footer">
        <span class="room-capacity"><i class="bi bi-people me-1"></i>do <?= (int) $room['capacity'] ?> osób</span>
        <a class="btn btn-sm btn-primary" href="<?= e(url('room.php?id=' . (int) $room['id'] . '#rezerwacja')) ?>">
            Rezerwuj <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</article>
