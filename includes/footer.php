<?php
/**
 * ============================================================
 * Footer Comum - Fecha o HTML aberto no header
 * ============================================================
 */

if (estaAutenticado()):
?>

        </div><!-- /page-content -->
    </main><!-- /main-content -->

    <!-- Rodapé simples global -->
    <footer style="
        text-align:center;
        padding:12px;
        font-size:12px;
        color:#9ca3af;
        background:transparent;
    ">
        Desenvolvido por <strong>NexaTech</strong>
    </footer>

    <script src="<?php echo APP_URL; ?>/public/assets/js/main.js?v=<?php echo APP_VERSION; ?>"></script>

    <?php if (isset($extraJS)): ?>
        <script src="<?php echo APP_URL; ?>/public/assets/js/<?php echo $extraJS; ?>?v=<?php echo APP_VERSION; ?>"></script>
    <?php endif; ?>

</body>
</html>

<?php
endif;

/**
 * 🔥 Garante flush do buffer se estiver ativo
 */
if (ob_get_level()) {
    ob_end_flush();
}
?>