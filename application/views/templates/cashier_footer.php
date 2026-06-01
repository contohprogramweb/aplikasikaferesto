    <script src="<?= base_url('assets/js/common.js') ?>"></script>
    <script src="<?= base_url('assets/js/cashier.js') ?>"></script>
    <script>
        // Pass config to JS
        window.CASHIER_CONFIG = {
            defaultTaxRate: <?= $default_tax_rate ?? 0.10 ?>,
            defaultServiceRate: <?= $default_service_rate ?? 0.05 ?>,
            csrfToken: '<?= $this->security->get_csrf_hash() ?>'
        };
    </script>
</body>
</html>
