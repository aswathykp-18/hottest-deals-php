            </main>
        </div>
    </div>
    <script src="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>assets/js/app.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>assets/js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
