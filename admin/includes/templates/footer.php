            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Versión</b> <?php echo APP_VERSION; ?>
        </div>
        <strong>Derechos de autor &copy; <?php echo date('Y'); ?> <a href="<?php echo SITE_URL; ?>"><?php echo SITE_NAME; ?></a>.</strong> Todos los derechos reservados.
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo ASSETS_URL; ?>/js/adminlte.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="<?php echo ASSETS_URL; ?>/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<!-- Select2 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/select2/js/select2.full.min.js"></script>
<!-- Summernote -->
<script src="<?php echo ASSETS_URL; ?>/plugins/summernote/summernote-bs4.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/plugins/summernote/lang/summernote-es-ES.min.js"></script>
<!-- Toastr -->
<script src="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.js"></script>
<!-- Custom scripts -->
<script src="<?php echo ASSETS_URL; ?>/js/custom.js"></script>

<!-- Scripts específicos de la página -->
<?php if (isset($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inicialización de componentes -->
<script>
$(function () {
    // Inicializar DataTables
    $('.datatable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "<?php echo ASSETS_URL; ?>/plugins/datatables/language/es-ES.json"
        }
    });

    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        language: 'es'
    });

    // Inicializar Summernote
    $('.summernote').summernote({
        height: 300,
        lang: 'es-ES',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ]
    });

    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar popovers
    $('[data-toggle="popover"]').popover();

    // Confirmación para acciones importantes
    $('.confirm-action').on('click', function(e) {
        if (!confirm('¿Está seguro de realizar esta acción? No se puede deshacer.')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php if (isset($inlineJs)): ?>
<script>
$(document).ready(function() {
    <?php echo $inlineJs; ?>
});
</script>
<?php endif; ?>

</body>
</html>
<?php
// Limpiar el buffer de salida y mostrarlo
ob_end_flush();
?>
